<?php

namespace Redseanet\Retailer\Controller\Sales;

use Exception;
use Redseanet\Retailer\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Collection\Order\Status as StatusCollection;
use Redseanet\Sales\Model\Shipment;
use Redseanet\Sales\Model\Shipment\Item;
use Redseanet\Sales\Model\Shipment\Track;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Status\History;
use TCPDF;

class ShipmentController extends AuthActionController
{
    use \Redseanet\Notifications\Traits\NotificationsMethod;

    public function editAction()
    {
        $root = $this->getLayout('admin_sales_shipment_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Shipment / CMS');
        } else {
            $root->getChild('head')->setTitle('Add New Shipment / CMS');
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Sales\\Model\\Shipment', ':ADMIN/sales_shipment/');
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['order_id', 'item_id', 'qty']);
            try {
                $order = new Order();
                $order->load($data['order_id']);
                if (!$order->canShip()) {
                    return $this->redirectReferer('retailer/transaction/product/');
                }
                $shipment = new Shipment();
                $shipment->setData($order->toArray())->setData([
                    'increment_id' => '',
                    'order_id' => $data['order_id'],
                    'comment' => $data['comment'] ?? ''
                ]);
                $this->beginTransaction();
                $shipment->setId(null)->save();
                foreach ($order->getItems(true) as $item) {
                    foreach ($data['item_id'] as $key => $id) {
                        if ($id == $item->getId()) {
                            $obj = new Item($item->toArray());
                            $obj->setData([
                                'id' => null,
                                'item_id' => $item->getId(),
                                'shipment_id' => $shipment->getId(),
                                'qty' => $data['qty'][$key]
                            ])->save();
                        }
                    }
                }

                if (isset($data['tracking']) && !empty($data['tracking']['number']) && !empty($data['tracking']['carrier'])) {
                    $track = new Track($data['tracking']);
                    $track->setData([
                        'shipment_id' => $shipment->getId(),
                        'order_id' => $data['order_id'],
                        'tracking_number' => $data['tracking']['number'],
                        'description' => $data['comment'] ?? ''
                    ])->save();
                    $segment = new Segment('customer');
                    $customerId = $segment->get('customer')['id'];
                    $orderUrl = $this->getBaseUrl('sales/order/view/?id=' . $data['order_id']);
                    $notificationsData = ['params' => json_encode(['orderid' => $data['order_id'], 'urlkey' => 'orderid']), 'area' => 'sales', 'level' => 'success', 'is_app' => 1, 'status' => 0, 'customer_id' => $order['customer_id'], 'sender_id' => $customerId, 'type' => 0];
                    $notificationsData['title'] = $this->translate('Your order %s just shipped', [$order['increment_id']]) . '.';
                    $notificationsData['content'] = $this->translate('Your order %s just shipped', [$order['increment_id']]) . '.';
                    $this->addNotifications($notificationsData);
                    $result['message'][] = ['message' => $this->translate('Your order %s just shipped', [$order['increment_id']]), 'level' => 'success'];
                }
                $code = (int) !$order->canShip() + (int) !$order->canInvoice();
                if ($code) {
                    $code = $code === 2 ? 'complete' : 'processing';
                    $status = new StatusCollection();
                    $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [], 'left')
                            ->where(['is_default' => 1, 'sales_order_phase.code' => $code])
                            ->limit(1);
                    $order->setData('status_id', $status[0]->getId())->save();
                    $history = new History();
                    $history->setData([
                        'admin_id' => null,
                        'order_id' => $order->getId(),
                        'status_id' => $status[0]->getId(),
                        'status' => $status[0]->offsetGet('name')
                    ])->save();
                }
                $this->commit();
            } catch (Exception $e) {
                $this->rollback();
                $this->getContainer()->get('log')->logException($e);
                $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                $result['error'] = 1;
            }
            return $this->response($result, $data['back_url'], 'retailer');
        }
        return $this->notFoundAction();
    }

    public function printAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            define('K_TCPDF_EXTERNAL_CONFIG', true);
            define('K_TCPDF_CALLS_IN_HTML', true);
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $root = $this->getLayout('admin_sales_shipment_print');
            $root->getChild('main', true)->setVariable('pdf', $pdf);
            $pdf->SetTitle($this->translate('Type Infomation'));
            $pdf->SetMargins(15, 27, 15);
            $pdf->setImageScale(1.25);
            $pdf->AddPage();
            $pdf->writeHTML($root->__toString(), true, false, true, false, '');
            $pdf->Output('order-' . $id, 'I');
        }
    }
}
