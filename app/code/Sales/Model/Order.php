<?php

namespace Redseanet\Sales\Model;

use Redseanet\Catalog\Model\Collection\Product\Review;
use Redseanet\Customer\Model\Address;
use Redseanet\Customer\Model\Customer;
use Redseanet\I18n\Model\Currency;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Collection\CreditMemo as CreditMemoCollection;
use Redseanet\Sales\Model\Collection\Invoice as InvoiceCollection;
use Redseanet\Sales\Model\Collection\Order\Item as ItemCollection;
use Redseanet\Sales\Model\Collection\Order\Status\History as HistoryCollection;
use Redseanet\Sales\Model\Collection\Rma as RmaCollection;
use Redseanet\Sales\Model\Collection\Shipment as ShipmentCollection;
use Redseanet\Sales\Model\Order\Item;
use Redseanet\Sales\Model\Order\Status;
use Redseanet\Sales\Model\Order\Status\History;

class Order extends AbstractModel
{
    use \Redseanet\Log\Traits\Ip;

    protected $items = null;
    protected $additional = null;
    protected $discount_detail = null;

    protected function construct()
    {
        $this->init('sales_order', 'id', [
            'id', 'status_id', 'increment_id', 'customer_id', 'language_id',
            'billing_address_id', 'shipping_address_id', 'warehouse_id', 'base_total_refunded',
            'store_id', 'billing_address', 'shipping_address', 'total_refunded',
            'is_virtual', 'free_shipping', 'base_currency', 'currency', 'base_subtotal',
            'shipping_method', 'payment_method', 'base_shipping', 'shipping', 'subtotal',
            'base_discount', 'discount', 'discount_detail', 'base_tax', 'tax', 'base_total', 'total',
            'base_total_paid', 'total_paid', 'additional', 'customer_note', 'coupon', 'ip'
        ]);
    }

    public function place($ids, $items, $cart, $statusId)
    {
        Bootstrap::getContainer()->get('log')->logException(new \Exception('place order ------'));
        Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($cart)));
        list($warehouseId, $storeId, $isVirtual) = explode('-', $ids);
        $note = json_decode($cart['customer_note'], true);
        $coupon = (isset($cart['coupon']) && $cart['coupon'] != '' ? json_decode($cart['coupon'], true) : []);
        unset($cart['created_at'], $cart['updated_at']);
        $cartData = $cart;
        $cartData['coupon'] = (isset($coupon[$storeId]) && $coupon[$storeId] ? $coupon[$storeId] : '');
        $cartData['shipping_method'] = $isVirtual ? '' : json_decode($cart['shipping_method'], true)[$storeId];
        $cartData['customer_note'] = $note[$storeId] ?? '';
        $cartData['warehouse_id'] = $warehouseId;
        $cartData['store_id'] = $storeId;
        $cartData['is_virtual'] = $isVirtual;
        $cartData['language_id'] = Bootstrap::getLanguage()->getId();
        $cartData['status_id'] = $statusId;
        Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($cartData)));
        $this->setData($cartData)->setId(null)->save();
        $orderId = $this->getId();
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = new Item($item);
            } else {
                $item = new Item($item->toArray());
            }
            $item->setData('order_id', $orderId)->setId(null)->save();
        }
        $this->collateTotals();
        //$this->getEventDispatcher()->trigger('order.place.after', ['model' => $this]);
        return $this;
    }

    public function getCustomer()
    {
        if (!empty($this->storage['customer_id'])) {
            $customer = new Customer($this->storage['language_id']);
            $customer->load($this->storage['customer_id']);
            if ($customer->getId()) {
                return $customer;
            }
        }
        return null;
    }

    public function getItems($force = false)
    {
        if ($force || is_null($this->items)) {
            $items = new ItemCollection();
            $items->where(['order_id' => $this->getId()]);
            $result = [];
            $items->walk(function ($item) use (&$result) {
                $result[$item['id']] = $item;
            });
            $this->items = $result;
            if ($force) {
                return $items;
            }
        }
        return $this->items;
    }

    public function collateTotals()
    {
        $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
        $currency = (new Currency())->load($this->getContainer()->get('request')->getCookie('currency', $baseCurrency));
        $items = $this->getItems(true);
        $baseSubtotal = 0;
        foreach ($items as $item) {
            $baseSubtotal += $item->collateTotals()->offsetGet('base_total');
        }
        $detail = $this->storage['discount_detail'] ? json_decode($this->storage['discount_detail'], true) : [];
        $discount_detail = [];
        $discount_detail['promotion']['total'] = isset($detail['promotion']['store_total'][$this->storage['store_id']]) ? $detail['promotion']['store_total'][$this->storage['store_id']] : 0;
        $discount_detail['promotion']['detail'] = isset($detail['promotion']['detail'][$this->storage['store_id']]) ? $detail['promotion']['detail'][$this->storage['store_id']] : [];
        $this->setData([
            'base_subtotal' => $baseSubtotal,
            'base_shipping' => $this->offsetGet('free_shipping') || $this->offsetGet('is_virtual') ? 0 : $this->getShippingMethod()->getShippingRate($items),
            'base_discount' => -(!empty($discount_detail['promotion']['total'] ? $discount_detail['promotion']['total'] : 0)) - (!empty($discount_detail['promotion']['retailer']) ? $discount_detail['promotion']['retailer'] : 0),
            'discount' => empty($discount_detail['promotion']['total']) ? 0 : -$currency->convert($discount_detail['promotion']['total']),
            'discount_detail' => json_encode($discount_detail),
            'base_tax' => 0,
            'tax' => 0
        ])->setData([
            'subtotal' => $currency->convert($this->storage['base_subtotal']),
            'shipping' => $currency->convert($this->storage['base_shipping'])
        ]);
        $this->getEventDispatcher()->trigger('tax.calc', ['model' => $this]);
        $this->getEventDispatcher()->trigger('promotion.calc', ['model' => $this]);
        $this->setData([
            'base_total' => $this->storage['base_subtotal'] +
            $this->storage['base_shipping'] +
            $this->storage['base_tax'] +
            $this->storage['base_discount'],
            'total' => $this->storage['subtotal'] +
            $this->storage['shipping'] +
            $this->storage['tax'] +
            $this->storage['discount']
        ]);
        if ($this->storage['base_total'] < 0 || $this->storage['total'] < 0) {
            throw new \Exception('An error detected.');
        }
        $this->save();
        return $this;
    }

    public function getShippingAddress()
    {
        if (isset($this->storage['shipping_address_id'])) {
            $address = (new Address())->load($this->storage['shipping_address_id']);
            return $address->getId() ? $address : null;
        }
        return null;
    }

    public function getBillingAddress()
    {
        if (isset($this->storage['billing_address_id'])) {
            $address = (new Address())->load($this->storage['billing_address_id']);
            return $address->getId() ? $address : null;
        }
        return null;
    }

    public function getAdditional($key = null)
    {
        if (is_null($this->additional)) {
            $this->additional = empty($this->storage['additional']) ? [] : json_decode($this->storage['additional'], true);
        }
        return $key ? ($this->additional[$key] ?? '') : $this->additional;
    }

    public function getDiscount($key = null)
    {
        if (is_null($this->discount_detail)) {
            $this->discount_detail = empty($this->storage['discount_detail']) ? [] : json_decode($this->storage['discount_detail'], true);
        }
        return $key ? ($this->discount_detail[$key] ?? '') : $this->discount_detail;
    }

    public function getCoupon()
    {
        if (!empty($this->storage['coupon'])) {
            return $this->storage['coupon'];
        }
        return '';
    }

    public function getShippingMethod()
    {
        if (isset($this->storage['shipping_method'])) {
            $className = $this->getContainer()->get('config')['shipping/' . $this->storage['shipping_method'] . '/model'];
            return new $className();
        }
        return null;
    }

    public function getPaymentMethod()
    {
        if (isset($this->storage['payment_method'])) {
            $className = $this->getContainer()->get('config')['payment/' . $this->storage['payment_method'] . '/model'];
            return new $className();
        }
        return null;
    }

    public function getBaseCurrency()
    {
        if (isset($this->storage['base_currency'])) {
            return (new Currency())->load($this->storage['base_currency'], 'code');
        }
        return $this->getContainer()->get('currency');
    }

    public function getCurrency()
    {
        if (isset($this->storage['currency'])) {
            return (new Currency())->load($this->storage['currency'], 'code');
        }
        return $this->getContainer()->get('currency');
    }

    public function getStatus()
    {
        if (isset($this->storage['status_id'])) {
            return (new Status())->load($this->storage['status_id']);
        }
        return null;
    }

    public function getPhase()
    {
        if ($status = $this->getStatus()) {
            return $status->getPhase();
        }
        return null;
    }

    public function getStatusHistory()
    {
        if ($this->getId()) {
            $history = new HistoryCollection();
            $history->where(['order_id' => $this->getId()])
                    ->order('created_at DESC');
            return $history;
        }
        return [];
    }

    public function getInvoice()
    {
        if ($this->getId()) {
            $collection = new InvoiceCollection();
            $collection->where(['order_id' => $this->getId()]);
            return $collection;
        }
        return [];
    }

    public function getShipment()
    {
        if ($this->getId()) {
            $collection = new ShipmentCollection();
            $collection->where(['order_id' => $this->getId()]);
            return $collection;
        }
        return [];
    }

    public function getCreditMemo()
    {
        if ($this->getId()) {
            $collection = new CreditMemoCollection();
            $collection->where(['order_id' => $this->getId()]);
            return $collection;
        }
        return [];
    }

    public function getQty()
    {
        $qty = 0;
        foreach ($this->getItems() as $item) {
            $qty += $item['qty'];
        }
        return $qty;
    }

    public function getWeight()
    {
        $qty = 0;
        foreach ($this->getItems() as $item) {
            $qty += $item['weight'];
        }
        return $qty;
    }

    public function getLanguage()
    {
        if (isset($this->storage['language_id'])) {
            return (new Language())->load($this->storage['language_id']);
        }
        return [];
    }

    public function getStore()
    {
        if (isset($this->storage['store_id'])) {
            return (new Store())->load($this->storage['store_id']);
        }
        return null;
    }

    public function canCancel()
    {
        return in_array($this->getPhase()->offsetGet('code'), ['pending', 'pending_payment']);
    }

    public function canHold()
    {
        return $this->getPhase()->offsetGet('code') === 'processing';
    }

    public function canUnhold()
    {
        return $this->getPhase()->offsetGet('code') === 'holded';
    }

    public function canInvoice()
    {
        if (in_array($this->getPhase()->offsetGet('code'), ['complete', 'canceled', 'closed', 'holded'])) {
            return false;
        }
        $invoices = $this->getInvoice();
        $qty = $this->getQty();
        foreach ($invoices->load(false) as $invoice) {
            foreach ($invoice->getItems(true)->load(false, true) as $item) {
                $qty -= $item['qty'];
            }
        }
        return $qty > 0;
    }

    public function canRepay()
    {
        return $this->getPhase()->offsetGet('code') === 'pending_payment' &&
                strtotime($this->offsetGet('created_at')) < strtotime('-5 minutes') &&
                $this->getContainer()->get('config')['payment/' . $this->offsetGet('payment_method') . '/gateway'];
    }

    public function canConfirm()
    {
        return $this->getPhase()->offsetGet('code') === 'complete' && !empty($this->getStatus()['is_default']);
    }

    public function canReview()
    {
        if ($this->getPhase()->offsetGet('code') !== 'complete' || !empty($this->getStatus()['is_default'])) {
            return false;
        }
        $collection = new Review();
        $collection->where(['order_id' => $this->getId()]);
        return $collection->count() === 0;
    }

    public function canShip()
    {
        if ($this->storage['is_virtual'] || in_array($this->getPhase()->offsetGet('code'), ['complete', 'canceled', 'closed', 'holded'])) {
            return false;
        }
        $shipments = $this->getShipment();
        $qty = $this->getQty();
        foreach ($shipments->load(false) as $shipment) {
            foreach ($shipment->getItems(true)->load(false, true) as $item) {
                $qty -= $item['qty'];
            }
        }
        return $qty > 0;
    }

    public function canRefund($flag = true)
    {
        if ($flag && !in_array($this->getPhase()->offsetGet('code'), ['holded', 'complete'])) {
            return false;
        } elseif (!$flag) {
            $code = $this->getPhase()->offsetGet('code');
            if (in_array($code, ['pending', 'pending_payment']) || $code === 'processing' && !$this->getStatus()->offsetGet('is_default')) {
                return false;
            }
            $applications = new RmaCollection();
            $applications->where(['order_id' => $this->getId()])
            ->where->notIn('status', [-2, -1, 5]);
            if (count($applications)) {
                return false;
            }
        }
        $memos = $this->getCreditMemo();
        $qty = $this->getQty();
        foreach ($memos as $memo) {
            foreach ($memo->getItems() as $item) {
                $qty -= $item['qty'];
            }
        }
        return $qty > 0;
    }

    public function rollbackStatus()
    {
        if ($this->getId()) {
            $history = new HistoryCollection();
            $history->join('sales_order_status', 'sales_order_status.id=sales_order_status_history.status_id', ['name'])
                    ->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                    ->where(['order_id' => $this->getId()])
                    ->order('created_at DESC')
                    ->limit(1)
            ->where->notEqualTo('status_id', $this->storage['status_id']);
            $user = new Segment('admin');
            if ($user->get('user')) {
                $userId = $user->get('user')->getId();
            } else {
                $userId = null;
            }
            if (count($history)) {
                $statusId = $history[0]->offsetGet('status_id');
                $statusName = $history[0]->offsetGet('name');
                $this->setData('status_id', $statusId)->save();
                (new History())->setData([
                    'admin_id' => $userId,
                    'order_id' => $this->getId(),
                    'status_id' => $statusId,
                    'status' => $statusName
                ])->save();
            }
        }
        return $this;
    }

    public function getRefundApplication()
    {
        $application = new RmaCollection();
        $application->where(['order_id' => $this->getId()])
                ->order('id DESC')
                ->limit(1);
        return count($application) ? $application[0] : null;
    }

    protected function beforeSave()
    {
        parent::beforeSave();
        $this->storage['ip'] = $this->getRealIp();
    }

    protected function afterSave()
    {
        if ($this->isNew) {
            $history = new Order\Status\History();
            $history->setData([
                'order_id' => $this->getId(),
                'status_id' => $this->offsetGet('status_id'),
                'status' => $this->getStatus()->offsetGet('name')
            ])->save();
        }
        parent::afterSave();
    }
}
