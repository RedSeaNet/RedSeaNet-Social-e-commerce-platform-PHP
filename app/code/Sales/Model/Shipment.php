<?php

namespace Redseanet\Sales\Model;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Sales\Model\Collection\Shipment\Item as ItemCollection;
use Redseanet\Sales\Model\Collection\Shipment\Track;

class Shipment extends AbstractModel
{
    protected $items = null;

    protected function construct()
    {
        $this->init('sales_order_shipment', 'id', [
            'id', 'order_id', 'increment_id', 'customer_id', 'store_id',
            'shipping_method', 'billing_address_id', 'shipping_address_id',
            'warehouse_id', 'store_id', 'billing_address', 'shipping_address',
            'comment', 'status'
        ]);
    }

    public function getItems($force = false)
    {
        if ($force || is_null($this->items)) {
            $items = new ItemCollection();
            $items->where(['shipment_id' => $this->getId()]);
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

    public function getOrder()
    {
        return isset($this->storage['order_id']) ?
                (new Order())->load($this->storage['order_id']) : null;
    }

    public function getShippingMethod()
    {
        if ($this->getId()) {
            $collection = new Track();
            $collection->where(['shipment_id' => $this->getId()]);
            return $collection;
        }
        return [];
    }
}
