<?php

namespace Redseanet\Sales\Controller;

use Error;
use Exception;
use Redseanet\Catalog\Model\Collection\Product\Review as ReviewCollection;
use Redseanet\Catalog\Model\Product\Review;
use Redseanet\Customer\Controller\AuthActionController;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Model;

class OrderController extends AuthActionController
{
    use \Redseanet\Lib\Traits\Rabbitmq;

    protected $allowedAction = [];

    public function listAction()
    {
        return $this->getLayout('sales_order_list');
    }

    public function jsonAction()
    {
        $condition = $this->getRequest()->getQuery();
        $customer = (new Segment('customer'))->get('customer')['id'];
        $collection = new Model\Collection\Order();
        $collection->where(['sales_order.customer_id' => $customer])
                ->order('created_at DESC');
        if (isset($condition['status'])) {
            $collection->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [], 'left')
                    ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left');
            if ($condition['status'] == 1) {
                $collection->where('(sales_order_phase.code=\'pending_payment\' OR sales_order_phase.code=\'pending\')');
            } elseif ($condition['status'] == 2) {
                $collection->where([
                    'sales_order_phase.code' => 'processing'
                ]);
            } elseif ($condition['status'] == 3) {
                $collection->where([
                    'sales_order_phase.code' => 'complete',
                    'sales_order_status.is_default' => 1
                ]);
            } elseif ($condition['status'] == 4) {
                $reviews = new ReviewCollection();
                $reviews->columns(['order_id'])
                        ->where(['review.customer_id' => $customer])
                ->where->isNotNull('review.order_id');
                $collection->notIn('sales_order.id', $reviews)
                        ->where([
                            'sales_order_phase.code' => 'complete',
                            'sales_order_status.is_default' => 0
                        ]);
            }
        }
        unset($condition['status']);
        if (isset($condition['id'])) {
            $collection->where(['sales_order.id' => $condition['id']]);
            unset($condition['id']);
        }
        $select = $collection->getSelect();
        $this->filter($select, $condition);
        $result = [];
        $collection->walk(function ($order) use (&$result) {
            $items = [];
            foreach ($order->getItems() as $item) {
                $product = $item['product'];
                $options = $item->offsetGet('options');
                $items[] = $item->toArray() + [
                    '_options' => $options ? $item->getOptions() : [],
                    '_product' => $product->toArray() + [
                        'thumbnail_url' => $product->getThumbnail($options ? json_decode($options, true) : null)
                    ]
                ];
            }
            $result[] = $order->toArray() + [
                '_payment_method' => $this->translate($order->getPaymentMethod()->getLabel()),
                '_shipping_method' => $this->translate($order->getShippingMethod()->getLabel()),
                'status' => $this->translate($order->getStatus()['name']),
                'items' => $items
            ];
        });
        return $result;
    }

    public function reviewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $root = $this->getLayout('sales_order_review');
            $order = new Model\Order();
            $order->load($id);
            $root->getChild('head')->setTitle($this->translate('Review Order #%s', [$order->offsetGet('increment_id')]));
            $content = $root->getChild('content');
            $content->setVariable('title', $this->translate('Review Order #%s', [$order->offsetGet('increment_id')]));
            $content->getChild('main')->setVariable('model', $order);
            return $root;
        }
        return $this->redirectReferer('sales/order/list');
    }

    public function reviewPostAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['order_id', 'review']);
            if ($result['error'] === 0) {
                try {
                    $segment = new Segment('customer');
                    $order = new Model\Order();
                    $order->load($data['order_id']);
                    if (!$order->getId() || !$order->canReview() ||
                            ($segment->get('hasLoggedIn') && $order->offsetGet('customer_id') !== $segment->get('customer')['id']) ||
                            (!$segment->get('hasLoggedIn') && $order->offsetGet('customer_id'))) {
                        throw new Exception('Invalid Order Id');
                    }
                    $files = $this->getRequest()->getUploadedFile();
                    $path = BP . 'pub/upload/review/';
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    foreach ($data['review'] as $productId => $content) {
                        $images = [];
                        if (!empty($files['image'][$productId])) {
                            $count = 0;
                            foreach ($files['image'][$productId] as $file) {
                                if ($file->getError() === UPLOAD_ERR_OK && $count++ < 5) {
                                    $newName = $file->getClientFilename();
                                    while (file_exists($path . $newName)) {
                                        $newName = preg_replace('/(\.[^\.]+$)/', random_int(0, 9) . '$1', $newName);
                                        if (strlen($newName) >= 120) {
                                            throw new Exception('The file is existed.');
                                        }
                                    }
                                    $file->moveTo($path . $newName);
                                    $images[] = $newName;
                                }
                            }
                        }
                        $review = new Review();
                        $review->setData([
                            'product_id' => $productId,
                            'customer_id' => $segment->get('hasLoggedIn') ? $segment->get('customer')['id'] : null,
                            'order_id' => $data['order_id'],
                            'language_id' => Bootstrap::getLanguage()->getId(),
                            'subject' => $data['subject'] ?? '',
                            'content' => $content,
                            'images' => json_encode($images),
                            'anonymous' => $data['anonymous'] ?? 0,
                            'rating' => ($data['rating'][0] ?? []) + ($data['rating'][$productId] ?? [])
                        ])->save();
                    }
                    $result['message'][] = ['message' => $this->translate('We have received your review. Thanks for your support.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please try again later.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'sales/order/list/', 'customer');
    }

    public function confirmAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $order = new Model\Order();
            $order->load($id);
            $result = ['error' => 0, 'message' => []];
            if ($order->getPhase()->offsetGet('code') === 'complete' && !empty($order->getStatus()['is_default'])) {
                try {
                    $status = $order->getPhase()->getStatus();
                    $status->where(['is_default' => 0]);
                    $order->setData('status_id', $status[0]->getId())->save();
                    $result['message'][] = ['message' => $this->translate('The order has been confirmed successfully.'), 'level' => 'success'];
                } catch (Error $e) {
                    $this->getContainer()->get('log')->logError($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please try again later.'), 'level' => 'danger'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please try again later.'), 'level' => 'danger'];
                }
            } else {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('Invalid Order Id'), 'level' => 'danger'];
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'sales/order/list/', 'customer');
    }

    public function viewAction($handler = 'sales_order_view')
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $order = new Model\Order();
            $order->load($id);
            $segment = new Segment('customer');
            if ($order->offsetGet('customer_id') == $segment->get('customer')['id']) {
                $root = $this->getLayout($handler);
                $root->getChild('head')->setTitle($this->translate('Order #%s', [$order->offsetGet('increment_id')], 'sales'));
                $root->getChild('main', true)->setVariable('order', $order);
                return $root;
            }
        }
        return $this->redirectReferer('sales/order/list');
    }

    public function invoiceAction()
    {
        if ($id = $this->getRequest()->getQuery('invoice')) {
            $model = new Model\Invoice();
            $model->load($id);
            if ($model->offsetGet('order_id') == $this->getRequest()->getQuery('id')) {
                $root = $this->viewAction('sales_order_invoice');
                if ($root instanceof Template) {
                    $root->getChild('pane', true)->setVariable('model', $model);
                }
                return $root;
            }
        }
        return $this->redirectReferer('sales/order/list');
    }

    public function shipmentAction()
    {
        if ($id = $this->getRequest()->getQuery('shipment')) {
            $model = new Model\Shipment();
            $model->load($id);
            if ($model->offsetGet('order_id') == $this->getRequest()->getQuery('id')) {
                $root = $this->viewAction('sales_order_shipment');
                if ($root instanceof Template) {
                    $root->getChild('pane', true)->setVariable('model', $model);
                }
                return $root;
            }
        }
        return $this->redirectReferer('sales/order/list');
    }

    public function creditMemoAction()
    {
        if ($id = $this->getRequest()->getQuery('creditmemo')) {
            $model = new Model\CreditMemo();
            $model->load($id);
            if ($model->offsetGet('order_id') == $this->getRequest()->getQuery('id')) {
                $root = $this->viewAction('sales_order_creditmemo');
                if ($root instanceof Template) {
                    $root->getChild('pane', true)->setVariable('model', $model);
                }
                return $root;
            }
        }
        return $this->redirectReferer('sales/order/list');
    }

    public function repayAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model\Order();
            $model->load($id);
            if ($model->canRepay()) {
                return $this->redirect($model->getPaymentMethod()->preparePayment([$model]));
            }
        }
        return $this->redirectReferer('sales/order/list');
    }
}
