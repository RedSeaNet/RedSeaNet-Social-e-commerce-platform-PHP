<?php

namespace Redseanet\Admin\Controller\Sales;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Collection\Rma as Collection;
use Redseanet\Sales\Model\Rma;
use Laminas\Db\Sql\Expression;

class RefundController extends AuthActionController
{
    public function statAction()
    {
        $collection = new Collection();
        $collection->columns(['count' => new Expression('count(sales_rma.id)')])
                ->join('sales_order', 'sales_order.id=sales_rma.order_id', ['payment_method'], 'left')
                ->group('sales_order.payment_method')
                ->where('sales_rma.service', [0, 1])
                ->where->greaterThanOrEqualTo('sales_rma.status', 0)
                ->lessThan('sales_rma.status', 5);
        $result = [];
        $collection->walk(function ($item) use (&$result) {
            if (isset($result[$item['payment_method']])) {
                $result[$item['payment_method']] += $item['count'];
            } else {
                $result[$item['payment_method']] = $item['count'];
            }
        });
        return $result;
    }

    public function indexAction()
    {
        $root = $this->getLayout('admin_refund_list');
        return $root;
    }

    public function viewAction()
    {
        $root = $this->getLayout('admin_refund_view');
        return $root;
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
                    $segment = new Segment('admin');
                    $storeId = $segment->get('user')['store_id'];
                    if ($storeId && $refund->getOrder()['store_id'] != $storeId ||
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
        $result = empty($id) ? ['error' => 1, 'message' => [['message' => $this->translate('Invalid application ID'), 'level' => 'danger']]] :
                ['error' => 0, 'message' => []];
        if ($result['error'] === 0) {
            try {
                $refund = new Rma();
                $refund->load($id);
                $segment = new Segment('admin');
                $storeId = $segment->get('user')['store_id'];
                if ($storeId && $refund->getOrder()['store_id'] != $storeId ||
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
        $result = empty($id) ? ['error' => 1, 'message' => [['message' => $this->translate('Invalid application ID'), 'level' => 'danger']]] :
                ['error' => 0, 'message' => []];
        $url = ':ADMIN/sales_refund/';
        if ($result['error'] === 0) {
            try {
                $url = ':ADMIN/sales_refund/view/?id=' . $id;
                $refund = new Rma();
                $refund->load($id);
                $segment = new Segment('admin');
                $storeId = $segment->get('user')['store_id'];
                if ($storeId && $refund->getOrder()['store_id'] != $storeId ||
                        $refund['status'] != 0 && $refund['status'] != 2 && ($refund['status'] != 3 || $refund['service'] != 1)) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid application ID'), 'level' => 'danger'];
                } else {
                    if ($refund['service'] == 0 || $refund['service'] == 1 && $refund['status'] == 3) {
                        $url = ':ADMIN/sales_order/refund/?id=' . $refund['order_id'] . '&rma_id=' . $id;
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
