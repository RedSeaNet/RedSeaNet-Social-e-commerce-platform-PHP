<?php

namespace Redseanet\Admin\Controller\Sales;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Collection\Order\Status as StatusCollection;
use Redseanet\Sales\Model\CreditMemo;
use Redseanet\Sales\Model\CreditMemo\Item;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Status\History;
use Redseanet\Sales\Model\Rma;
use TCPDF;

class CreditmemoController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_sales_creditmemo_list');
        return $root;
    }

    public function viewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            return $this->getLayout('admin_sales_creditmemo_view');
        }
        return $this->notFoundAction();
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Sales\\Model\\CreditMemo', ':ADMIN/sales_creditmemo/');
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['order_id', 'item_id', 'qty']);
            try {
                $order = new Order();
                $order->load($data['order_id']);
                if (!$order->canRefund()) {
                    return $this->redirectReferer(':ADMIN/sales_order/view/?id=' . $data['order_id']);
                }
                $memo = new CreditMemo();
                $memo->setData($order->toArray())->setData([
                    'increment_id' => '',
                    'order_id' => $data['order_id'],
                    'comment' => $data['comment'] ?? ''
                ]);
                if (empty($data['include_shipping'])) {
                    $memo->setData([
                        'base_shipping' => 0,
                        'shipping' => 0
                    ]);
                }
                if (empty($data['include_tax'])) {
                    $memo->setData([
                        'base_tax' => 0,
                        'tax' => 0
                    ]);
                }
                $this->beginTransaction();
                $memo->setId(null)->save();
                foreach ($order->getItems(true) as $item) {
                    foreach ($data['item_id'] as $key => $id) {
                        if ($id == $item->getId()) {
                            $obj = new Item($item->toArray());
                            $obj->setData([
                                'id' => null,
                                'item_id' => $item->getId(),
                                'creditmemo_id' => $memo->getId(),
                                'qty' => $data['qty'][$key]
                            ])->collateTotals()->save();
                        }
                    }
                }
                $memo->collateTotals()->save();
                $this->getContainer()->get('eventDispatcher')->trigger('order.refund.after', ['model' => $memo]);
                $order->setData([
                    'base_total_refunded' => (float) $order['base_total_refunded'] + $memo['base_total'],
                    'total_refunded' => (float) $order['total_refunded'] + $memo['total']
                ]);
                if (!$order->canRefund()) {
                    $status = new StatusCollection();
                    $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                            ->where(['is_default' => 1, 'sales_order_phase.code' => 'closed'])
                            ->limit(1);
                    $order->setData('status_id', $status[0]->getId());
                    $history = new History();
                    $history->setData([
                        'admin_id' => (new Segment('admin'))->get('user')['id'],
                        'order_id' => $order->getId(),
                        'status_id' => $status[0]->getId(),
                        'status' => $status[0]->offsetGet('name')
                    ])->save();
                }
                $order->save();
                if (!empty($data['rma_id'])) {
                    $rma = new Rma();
                    $rma->setData(['id' => $data['rma_id'], 'status' => 5])
                            ->save();
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

    public function printAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            define('K_TCPDF_EXTERNAL_CONFIG', true);
            define('K_TCPDF_CALLS_IN_HTML', true);
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $root = $this->getLayout('admin_sales_creditmemo_print');
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
