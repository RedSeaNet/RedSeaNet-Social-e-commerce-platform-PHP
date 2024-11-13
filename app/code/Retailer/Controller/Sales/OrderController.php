<?php

namespace Redseanet\Retailer\Controller\Sales;

use Exception;
use Redseanet\Customer\Model\Address;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Controller\AuthActionController;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Sales\Model\Collection\Order as Collection;
use Redseanet\Sales\Model\Collection\Order\Status;
use Redseanet\Sales\Model\Order as Model;
use Redseanet\Sales\Model\Order\Status\History;
use TCPDF;
use Laminas\Db\Sql\Expression;
use Redseanet\I18n\Model\Currency;

class OrderController extends AuthActionController
{
    use \Redseanet\Admin\Traits\Stat;

    public function chartAction()
    {
        $segment = new Segment('customer');
        $retailer = new Retailer();
        $retailer->load($segment->get('customer')['id'], 'customer_id');
        $collection = new Collection();
        $collection->columns(['count' => new Expression('count(1)')])
                ->where(['store_id' => $retailer->offsetGet('store_id')]);
        return $this->stat(
            $collection,
            function ($collection) {
                return $collection[0]['count'] ?? 0;
            }
        );
    }

    public function amountAction()
    {
        $segment = new Segment('customer');
        $retailer = new Retailer();
        $retailer->load($segment->get('customer')['id'], 'customer_id');
        $collection = new Collection();
        $collection->columns(['base_currency', 'total' => new Expression('sum(base_total)'), 'refunded' => new Expression('sum(base_total_refunded)')])
                ->join('sales_order_status', 'sales_order.status_id=sales_order_status.id', [], 'left')
                ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                ->group('base_currency')
                ->where([
                    'sales_order_phase.code' => 'complete',
                    'store_id' => $retailer->offsetGet('store_id')
                ]);
        $currency = $this->getContainer()->get('currency');
        $code = $currency->offsetGet('code');
        $result = $this->stat($collection, function ($collection) use ($code) {
            $result = 0;
            foreach ($collection as $item) {
                $result += $item->offsetGet('base_currency') == $code ?
                        $item->offsetGet('total') - $item->offsetGet('refunded') :
                        $item->getBaseCurrency()->rconvert($item->offsetGet('total'), false) - $item->getBaseCurrency()->rconvert($item->offsetGet('refunded'), false);
            }
            return $result;
        });
        $result['amount'] = $currency->format($result['amount']);
        $result['daily'] = $currency->format($result['daily']);
        $result['monthly'] = $currency->format($result['monthly']);
        $result['yearly'] = $currency->format($result['yearly']);
        return $result;
    }

    public function indexAction()
    {
        return $this->getLayout('retailer_sales_order_list');
    }

    public function viewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $root = $this->getLayout('retailer_sales_order_view');
            $order = new Model();
            $order->load($id);
            if ($order->getId()) {
                $root->getChild('head')->setTitle($this->translate('Order') . ' #' . $order['increment_id']);
                $root->getChild('main', true)->setVariable('order', $order);
                return $root;
            }
        }
        return $this->redirectReferer('retailer/sales_order/');
    }

    public function cancelAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $result = ['error' => 0, 'message' => []];
            try {
                $status = new Status();
                $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                        ->where(['is_default' => 1, 'sales_order_phase.code' => 'canceled'])
                        ->limit(1);
                $count = 0;
                $statusId = $status[0]->getId();
                $dispatcher = $this->getContainer()->get('eventDispatcher');
                $this->beginTransaction();
                $order = new Model();
                $order->load($id);
                if ($order->canCancel()) {
                    $history = new History();
                    $history->setData([
                        'admin_id' => null,
                        'order_id' => $id,
                        'status_id' => $statusId,
                        'status' => $status[0]->offsetGet('name')
                    ])->save();
                    $order->setData('status_id', $statusId)->save();
                    $dispatcher->trigger('order.cancel.after', ['model' => $order]);
                    $count++;
                }
                $this->commit();
                $result['message'][] = ['message' => $this->translate('%d order(s) has been canceled.', [count((array) $id)]), 'level' => 'success'];
                return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'retailer');
            } catch (Exception $e) {
                $this->rollback();
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while canceling orders.'), 'level' => 'danger'];
            }
        }
        return $this->redirectReferer('retailer/transaction/product/');
    }

    public function holdAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $result = ['error' => 0, 'message' => []];
            try {
                $status = new Status();
                $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                        ->where(['is_default' => 1, 'sales_order_phase.code' => 'holded'])
                        ->limit(1);
                $count = 0;
                $statusId = $status[0]->getId();
                $this->beginTransaction();
                foreach ((array) $id as $i) {
                    $order = new Model();
                    $order->load($i);
                    if ($order->canHold()) {
                        $history = new History();
                        $history->setData([
                            'admin_id' => null,
                            'order_id' => $i,
                            'status_id' => $statusId,
                            'status' => $status[0]->offsetGet('name')
                        ])->save();
                        $order->setData('status_id', $statusId)
                                ->save();
                        $count++;
                    }
                }
                $this->commit();
                $result['message'][] = ['message' => $this->translate('%d order(s) has been holded.', [count((array) $id)]), 'level' => 'success'];
                return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'retailer');
            } catch (Exception $e) {
                $this->rollback();
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while holding orders.'), 'level' => 'danger'];
            }
        }
        return $this->redirectReferer('retailer/transaction/product/');
    }

    public function unholdAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $result = ['error' => 0, 'message' => []];
            try {
                $status = new Status();
                $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                        ->where(['is_default' => 1, 'sales_order_phase.code' => 'processing'])
                        ->limit(1);
                $count = 0;
                $this->beginTransaction();
                foreach ((array) $id as $i) {
                    $order = new Model();
                    $order->load($i);
                    if ($order->canUnhold()) {
                        $order->rollbackStatus();
                        $count++;
                    }
                }
                $this->commit();
                $result['message'][] = ['message' => $this->translate('%d order(s) has been unholded.', [count((array) $id)]), 'level' => 'success'];
                return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'retailer');
            } catch (Exception $e) {
                $this->rollback();
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while unholding orders.'), 'level' => 'danger'];
            }
        }
        return $this->redirectReferer('retailer/transaction/product/');
    }

    public function statusAction()
    {
        return $this->doSave(
            '\\Redseanet\\Sales\\Model\\Order\\Status\\History',
            $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'],
            [],
            function ($model, $data) {
                $order = new Model();
                $order->load($data['id']);
                $collection = $order->getStatus()->getPhase()->getStatus();
                $flag = false;
                foreach ($collection as $status) {
                    if ($status['id'] === $data['status_id']) {
                        $flag = $status['name'];
                        break;
                    }
                }
                if ($flag === false) {
                    throw new Exception('Invalid status.');
                }
                $order->setData('status_id', $data['status_id'])->save();
                $model->setData([
                    'id' => null,
                    'admin_id' => null,
                    'order_id' => $data['id'],
                    'status' => $flag,
                    'is_customer_notified' => (int) isset($data['is_customer_notified']),
                    'is_visible_on_front' => (int) isset($data['is_visible_on_front'])
                ]);
            }
        );
    }

    public function shipAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $order = new Model();
            $order->load($id);
            if ($order->canShip()) {
                $root = $this->getLayout('retailer_sales_shipment_edit');
                return $root;
            }
        }
        return $this->notFoundAction();
    }

    public function invoiceAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $order = new Model();
            $order->load($id);
            if ($order->canInvoice()) {
                $root = $this->getLayout('retailer_sales_invoice_edit');
                return $root;
            }
        }
        return $this->notFoundAction();
    }

    public function refundAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $order = new Model();
            $order->load($id);
            if ($order->canRefund()) {
                $root = $this->getLayout('retailer_sales_creditmemo_edit');
                return $root;
            }
        }
        return $this->notFoundAction();
    }

    public function saveAddressAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['order_id', 'id', 'is_billing']);
            if ($result['error'] === 0) {
                try {
                    $order = new Model();
                    $order->load($data['order_id']);
                    $address = new Address();
                    $address->load($data['id']);
                    $order->setData($data['is_billing'] ? 'billing_address' : 'shipping_address', $address->setData($data)->display(false))->save();
                    $result['reload'] = 1;
                    return $this->response($result, ':ADMIN/sales_order/view/?id=' . $data['order_id'], 'retailer');
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? [], 'retailer/sales_order/');
    }

    public function saveDiscountAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id', 'discount']);
            if ($result['error'] === 0) {
                try {
                    $order = new Model();
                    $order->load($data['id']);
                    $segment = new Segment('customer');
                    if ($order->canCancel()) {
                        $currency = (new Currency())->load($order['currency']);
                        $detail = $order->offsetGet('discount_detail') ? json_decode($order->offsetGet('discount_detail'), true) : [];
                        $detail['retailer'] = ['total' => $data['discount'], 'customer_id' => $segment->get('customer')['id']];
                        $base_discount = -(!empty($detail['promotion']['total']) ? $detail['promotion']['total'] : 0) - $data['discount'];
                        $order->setData('discount_detail', json_encode($detail));
                        $order->setData('base_discount', $base_discount);
                        $order->setData('discount', $currency->convert($base_discount));
                        $order->setData('base_total', $order['base_subtotal'] + $order['base_shipping'] + $order['base_tax'] + $order['base_discount']);
                        $order->setData('total', $order['subtotal'] + $order['shipping'] + $order['tax'] + $order['discount']);
                        $order->save();
                        $result['reload'] = 1;
                        return $this->response($result, ':ADMIN/sales_order/view/?id=' . $data['id'], 'retailer');
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? [], 'retailer/sales_order/');
    }

    public function printAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            define('K_TCPDF_EXTERNAL_CONFIG', true);
            define('K_TCPDF_CALLS_IN_HTML', true);
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $root = $this->getLayout('admin_sales_order_print');
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
