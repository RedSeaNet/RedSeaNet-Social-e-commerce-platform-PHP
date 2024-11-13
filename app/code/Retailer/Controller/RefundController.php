<?php

namespace Redseanet\Retailer\Controller;

use Redseanet\Sales\Model\Rma;

class RefundController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('retailer_refund');
    }

    public function viewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $rma = new Rma();
            $rma->load($id);
            if ($rma->getId()) {
                $root = $this->getLayout('retailer_refund_view');
                $root->getChild('main', true)->setVariable('model', $rma);
                return $root;
            }
        }
        return $this->redirectReferer('retailer/refund/');
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
                            'is_customer' => 0,
                            'comment' => $data['comment'],
                            'image' => json_encode($images)
                        ]);
                        $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
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

    public function deliverAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['rma_id', 'carrier', 'tracking_number']);
            if ($result['error'] === 0) {
                try {
                    $refund = new Rma();
                    $refund->load($data['rma_id']);
                    $retailer = $this->getRetailer();
                    if (!$retailer || !$retailer->getId() ||
                            $refund->getOrder()['store_id'] != $retailer['store_id'] ||
                            $refund['service'] != 2 || $refund['status'] != 3) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('Invalid application ID'), 'level' => 'danger'];
                    } else {
                        $refund->setData('status', 5)->save()->addComment([
                            'is_customer' => 0,
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
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/refund/', 'customer');
    }

    public function refuseAction()
    {
        $id = $this->getRequest()->getQuery('id');
        $result = empty($id) ? ['error' => 1, 'message' => [['title' => $this->translate('Invalid application ID'), 'content' => $this->translate('Invalid application ID'), 'level' => 'danger']]] :
                ['error' => 0, 'message' => []];
        if ($result['error'] === 0) {
            try {
                $refund = new Rma();
                $refund->load($id);
                $retailer = $this->getRetailer();
                if (!$retailer || !$retailer->getId() ||
                        $refund->getOrder()['store_id'] != $retailer['store_id'] ||
                        $refund['status'] != 0 && $refund['status'] != 2 && ($refund['status'] != 3 || $refund['service'] != 1)) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid application ID'), 'level' => 'danger'];
                } else {
                    $this->beginTransaction();
                    $refund->setData('status', -1)->save();
                    $refund->getOrder()->rollbackStatus();
                    $result['message'][] = ['message' => $this->translate('The application has been refused.'), 'level' => 'success'];
                    $this->commit();
                }
            } catch (Exception $e) {
                $this->rollback();
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, 'retailer/refund/view/?id=' . $id, 'customer');
    }

    public function approveAction()
    {
        $id = $this->getRequest()->getQuery('id');
        $result = empty($id) ? ['error' => 1, 'message' => [['title' => $this->translate('Invalid application ID'), 'content' => $this->translate('Invalid application ID'), 'level' => 'danger']]] :
                ['error' => 0, 'message' => []];
        $url = 'retailer/refund/';
        if ($result['error'] === 0) {
            try {
                $url = 'retailer/refund/view/?id=' . $id;
                $refund = new Rma();
                $refund->load($id);
                $retailer = $this->getRetailer();
                if (!$retailer || !$retailer->getId() ||
                        $refund->getOrder()['store_id'] != $retailer['store_id'] ||
                        $refund['status'] != 0 && $refund['status'] != 2 && ($refund['status'] != 3 || $refund['service'] != 1)) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid application ID'), 'level' => 'danger'];
                } else {
                    if ($refund['service'] == 0 || $refund['service'] == 1 && $refund['status'] == 3) {
                        $url = 'retailer/sales_order/refund/?id=' . $refund['order_id'] . '&rma_id=' . $id;
                    }
                    $this->beginTransaction();
                    $refund->setData('status', $refund['status'] + 1)->save();
                    $result['message'][] = ['message' => $this->translate('The application has been approved.'), 'level' => 'success'];
                    $this->commit();
                }
            } catch (Exception $e) {
                $this->rollback();
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, $url, 'customer');
    }
}
