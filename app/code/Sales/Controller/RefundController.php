<?php

namespace Redseanet\Sales\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Exception\FileSizeExceedLimitException;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Phase;
use Redseanet\Sales\Model\Rma;

class RefundController extends ActionController
{
    use \Redseanet\Lib\Traits\DB;

    public function indexAction()
    {
        return $this->getLayout('sales_refund');
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

    public function viewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $rma = new Rma();
            $rma->load($id);
            if ($rma->getId()) {
                $root = $this->getLayout('sales_refund_view');
                $root->getChild('main', true)->setVariable('model', $rma);
                return $root;
            }
        }
        return $this->redirectReferer('sales/refund/');
    }

    public function addCommentAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['rma_id', 'comment']);
            if ($result['error'] === 0) {
                $refund = new Rma();
                $refund->load($data['rma_id']);
                if ($refund['status'] > 4 || $refund['status'] < 0) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid application ID'), 'level' => 'danger'];
                }
                if ($result['error'] === 0) {
                    try {
                        $images = [];
                        $path = BP . 'pub/upload/refund/';
                        if (!is_dir($path)) {
                            mkdir($path, 0777, true);
                        }
                        $count = 0;
                        $files = $this->getRequest()->getUploadedFile();
                        if (!empty($files['voucher'])) {
                            foreach ($files['voucher'] as $file) {
                                if ($file->getError() === UPLOAD_ERR_OK && $count++ < 5) {
                                    if ($file->getSize() > 2097152) {
                                        throw new FileSizeExceedLimitException('The size of the uploaded file exceed the limitation.');
                                    }
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
                        $refund->addComment([
                            'is_customer' => 1,
                            'comment' => $data['comment'],
                            'image' => json_encode($images)
                        ]);
                        $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                    } catch (FileSizeExceedLimitException $e) {
                        $this->rollback();
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                    } catch (Exception $e) {
                        $this->rollback();
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                    }
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'sales/refund/', 'customer');
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['order_id', 'service', 'reason', 'qty', 'comment']);
            if ($result['error'] === 0 && array_sum($data['qty']) === 0) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('Please select 1 product at least.'), 'level' => 'danger'];
            }
            if ($result['error'] === 0) {
                $segment = new Segment('customer');
                $order = (new Order())->load($data['order_id']);
                if ($segment->get('hasLoggedIn')) {
                    $customerId = $segment->get('customer')['id'];
                    if ($customerId !== $order['customer_id']) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('Invalid order ID'), 'level' => 'danger'];
                    }
                }
                if ($order->getId() && $order->canRefund(false)) {
                    $refund = new Rma($data);
                    $refund->setData([
                        'order_id' => $order->getId(),
                        'customer_id' => $customerId ?? null
                    ]);
                    $images = [];
                    try {
                        $path = BP . 'pub/upload/refund/';
                        if (!is_dir($path)) {
                            mkdir($path, 0777, true);
                        }
                        $count = 0;
                        $files = $this->getRequest()->getUploadedFile();
                        if (!empty($files['voucher'])) {
                            foreach ($files['voucher'] as $file) {
                                if ($file->getError() === UPLOAD_ERR_OK && $count++ < 5) {
                                    if ($file->getSize() > 2097152) {
                                        throw new FileSizeExceedLimitException('The size of the uploaded file exceed the limitation.');
                                    }
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
                        $this->beginTransaction();
                        $refund->save()->addComment([
                            'is_customer' => 1,
                            'comment' => $data['comment'],
                            'image' => json_encode($images)
                        ]);
                        $status = (new Phase())->load('holded', 'code')->getDefaultStatus()->getId();
                        if ($order->offsetGet('status_id') != $status) {
                            $history = new Order\Status\History();
                            $history->load($order->getId(), 'order_id');
                            if (!$history->getId()) {
                                $history = new Order\Status\History();
                                $history->setData([
                                    'order_id' => $order->getId(),
                                    'status_id' => $order->offsetGet('status_id'),
                                    'status' => $order->getStatus()->offsetGet('name')
                                ])->save();
                            }
                            $order->setData('status_id', $status)->save();
                        }
                        $result['message'][] = ['message' => $this->translate('We have received your application. The customer service will contact you as soon as possible. Thanks for your support.'), 'level' => 'success'];
                        $this->commit();
                    } catch (FileSizeExceedLimitException $e) {
                        $this->rollback();
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                    } catch (Exception $e) {
                        $this->rollback();
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                    }
                } else {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid order ID'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'sales/refund/', 'customer');
    }

    public function deliverAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['rma_id', 'carrier', 'tracking_number']);
            if ($result['error'] === 0) {
                try {
                    $refund = new Rma();
                    $refund->load($data['rma_id']);
                    $segment = new Segment('customer');
                    if ($segment->get('hasLoggedIn') && $segment->get('customer')['id'] != $refund['customer_id'] ||
                            !$segment->get('hasLoggedIn') && $refund['customer_id'] ||
                            !$refund['service'] && $refund['status'] != 1) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('Invalid application ID'), 'level' => 'danger'];
                    } else {
                        $refund->setData('status', 2)->save()->addComment([
                            'is_customer' => 1,
                            'comment' => $this->translate('Carrier: %s<br />Tracking Number: %s', [$data['carrier'], $data['tracking_number']]),
                        ]);
                        $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'sales/refund/', 'customer');
    }

    public function confirmAction()
    {
        $id = $this->getRequest()->getQuery('id');
        $result = empty($id) ? ['error' => 1, 'message' => [['title' => $this->translate('Invalid application ID'), 'content' => $this->translate('Invalid application ID'), 'level' => 'danger']]] :
                ['error' => 0, 'message' => []];
        if ($result['error'] === 0) {
            try {
                $refund = new Rma();
                $refund->load($id);
                $segment = new Segment('customer');
                if ($segment->get('hasLoggedIn') && $segment->get('customer')['id'] != $refund['customer_id'] ||
                        !$segment->get('hasLoggedIn') && $refund['customer_id'] ||
                        $refund['service'] < 2 || $refund['status'] != 4) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid application ID'), 'level' => 'danger'];
                } else {
                    $this->beginTransaction();
                    $refund->setData('status', 5)->save();
                    $refund->getOrder()->rollbackStatus();
                    $result['message'][] = ['message' => $this->translate('The application has been complete.'), 'level' => 'success'];
                    $this->commit();
                }
            } catch (Exception $e) {
                $this->rollback();
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, 'sales/refund/view/?id=' . $id, 'customer');
    }

    public function cancelAction()
    {
        $id = $this->getRequest()->getQuery('id');
        $result = empty($id) ? ['error' => 1, 'message' => [['title' => $this->translate('Invalid application ID'), 'content' => $this->translate('Invalid application ID'), 'level' => 'danger']]] :
                ['error' => 0, 'message' => []];
        if ($result['error'] === 0) {
            try {
                $refund = new Rma();
                $refund->load($id);
                $segment = new Segment('customer');
                if ($segment->get('hasLoggedIn') && $segment->get('customer')['id'] != $refund['customer_id'] ||
                        !$segment->get('hasLoggedIn') && $refund['customer_id'] ||
                        $refund['status'] != 0) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid application ID'), 'level' => 'danger'];
                } else {
                    $this->beginTransaction();
                    $refund->setData('status', -2)->save();
                    $refund->getOrder()->rollbackStatus();
                    $result['message'][] = ['message' => $this->translate('The application has been complete.'), 'level' => 'success'];
                    $this->commit();
                }
            } catch (Exception $e) {
                $this->rollback();
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, 'sales/refund/view/?id=' . $id, 'customer');
    }
}
