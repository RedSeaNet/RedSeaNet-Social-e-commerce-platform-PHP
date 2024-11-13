<?php

namespace Redseanet\Admin\Controller\Sales;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Collection\Order\Status as StatusCollection;
use Redseanet\Sales\Model\Invoice;
use Redseanet\Sales\Model\Invoice\Item;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Status\History;
use Redseanet\Customer\Model\Balance;
use TCPDF;
use Redseanet\Lib\Source\Store;

class InvoiceController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_sales_invoice_list');
        return $root;
    }

    public function viewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            return $this->getLayout('admin_sales_invoice_view');
        }
        return $this->notFoundAction();
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_sales_invoice_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Invoice / CMS');
        } else {
            $root->getChild('head')->setTitle('Add New Invoice / CMS');
        }
        return $root;
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['order_id', 'item_id', 'qty']);
            try {
                $order = new Order();
                $order->load($data['order_id']);
                if (!$order->canInvoice()) {
                    return $this->redirectReferer(':ADMIN/sales_order/view/?id=' . $data['order_id']);
                }
                $invoice = new Invoice();
                $invoice->setData($order->toArray())->setData([
                    'increment_id' => '',
                    'order_id' => $data['order_id'],
                    'comment' => $data['comment'] ?? ''
                ]);
                if (empty($data['include_shipping'])) {
                    $invoice->setData([
                        'base_shipping' => 0,
                        'shipping' => 0
                    ]);
                }
                if (empty($data['include_tax'])) {
                    $invoice->setData([
                        'base_tax' => 0,
                        'tax' => 0
                    ]);
                }
                $this->beginTransaction();
                $invoice->setId(null)->save();
                foreach ($order->getItems(true) as $item) {
                    foreach ($data['item_id'] as $key => $id) {
                        if ($id == $item->getId()) {
                            $obj = new Item($item->toArray());
                            $obj->setData([
                                'id' => null,
                                'item_id' => $item->getId(),
                                'invoice_id' => $invoice->getId(),
                                'qty' => $data['qty'][$key]
                            ])->collateTotals()->save();
                        }
                    }
                }
                $invoice->collateTotals();
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

    public function printAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            define('K_TCPDF_EXTERNAL_CONFIG', true);
            define('K_TCPDF_CALLS_IN_HTML', true);
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $root = $this->getLayout('admin_sales_invoice_print');
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
