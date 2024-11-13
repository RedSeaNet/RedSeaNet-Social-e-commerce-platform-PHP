<?php

namespace Redseanet\Sales\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Catalog\Model\Product\Review;
use Redseanet\Sales\Model\Order;

class InquireController extends ActionController
{
    public function indexAction()
    {
        return $this->getLayout('sales_order_inquire');
    }

    public function viewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $root = $this->getLayout('sales_order_view');
            $order = new Order();
            $order->load($id);
            $root->getChild('head')->setTitle($this->translate('Order #%s', [$order->offsetGet('increment_id')], 'sales'));
            $root->getChild('main', true)->setVariable('order', $order);
            return $root;
        }
        return $this->redirectReferer('sales/order/list');
    }

    public function reviewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $root = $this->getLayout('sales_order_review');
            $order = new Order();
            $order->load($id);
            $root->getChild('head')->setTitle($this->translate('Review Order #%s', [$order->offsetGet('increment_id')]));
            $content = $root->getChild('content');
            $content->setVariable('title', $this->translate('Review Order #%s', [$order->offsetGet('increment_id')]));
            $content->getChild('main')->setVariable('model', $order);
            return $root;
        }
        return $this->redirectReferer('sales/inquire/inquire');
    }

    public function reviewPostAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['order_id', 'review']);
            if ($result['error'] === 0) {
                try {
                    $order = new Order();
                    $order->load($data['order_id']);
                    if (!$order->getId() || !$order->canReview()) {
                        throw new Exception('Invalid Order Id');
                    }
                    $files = $this->getRequest()->getUploadedFile();
                    $path = BP . 'pub/upload/review/';
                    if (!is_dir($path)) {
                        mkdir($path, 0644, true);
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
        return $this->response($result ?? ['error' => 0, 'message' => []], 'sales/inquire/inquire/');
    }

    public function applyAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $order = new Order();
            $order->load($id);
            if ($order->getId() && $order->canRefund(false)) {
                $root = $this->getLayout('sales_refund_apply');
                $root->getChild('main', true)->setVariable('model', $order);
                return $root;
            }
        }
        return $this->notFoundAction();
    }
}
