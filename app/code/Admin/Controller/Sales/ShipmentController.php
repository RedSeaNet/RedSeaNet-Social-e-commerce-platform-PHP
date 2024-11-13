<?php

namespace Redseanet\Admin\Controller\Sales;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
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
    public function indexAction()
    {
        $root = $this->getLayout('admin_sales_shipment_list');
        return $root;
    }

    public function viewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            return $this->getLayout('admin_sales_shipment_view');
        }
        return $this->notFoundAction();
    }

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
                    return $this->redirectReferer(':ADMIN/sales_order/view/?id=' . $data['order_id']);
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
                        'tracking_number' => $data['tracking']['number']
                    ])->save();
                }
                $this->getContainer()->get('eventDispatcher')->trigger('order.shipped', ['model' => $shipment, 'track' => $track ?? null, 'isNew' => true]);
                $code = (int) !$order->canShip() + (int) !$order->canInvoice();
                if ($code) {
                    $code = $code === 2 ? 'complete' : 'processing';
                    $status = new StatusCollection();
                    $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                            ->where(['is_default' => 1, 'sales_order_phase.code' => $code])
                            ->limit(1);
                    $order->setData('status_id', $status[0]->getId())->save();
                    $history = new History();
                    $history->setData([
                        'admin_id' => (new Segment('admin'))->get('user')['id'],
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
            return $this->response($result, ':ADMIN/sales_order/view/?id=' . $data['order_id']);
        }
        return $this->notFoundAction();
    }

    public function trackAction()
    {
        $url = ':ADMIN/sales_shipment/';
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['shipment_id', 'order_id', 'tracking_number', 'carrier']);
            if ($result['error'] === 0) {
                try {
                    $track = new Track($data + ['created_at' => date('Y-m-d H:i:s')]);
                    if (!empty($data['carrier_code']) && !empty($data['schedule'])) {
                        $track->setData('schedule', true);
                    }
                    $track->save();
                    $result['message'][] = ['message' => $this->translate('The shipment has been tracking.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $url);
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
