<?php

namespace Redseanet\Admin\ViewModel\Sales\View;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Model\Cart as Model;
use Redseanet\Sales\Model\Collection\Cart\Item as Collection;

class Cart extends Template
{
    protected $cart = null;

    public function getCart()
    {
        if (is_null($this->cart)) {
            $this->cart = (new Model(['id' => $this->getQuery('id')]))->load($this->getQuery('id'));
        }
        return $this->cart;
    }

    public function getCustomer()
    {
        if ($id = $this->getCart()->offsetGet('customer_id')) {
            $customer = new Customer();
            $customer->load($id);
            return $customer;
        }
        return null;
    }

    public function getCollection()
    {
        $collection = new Collection();
        $collection->columns(['product_id', 'product_name', 'store_id', 'sku', 'options', 'qty', 'status', 'sku', 'price', 'total'])
                ->join('core_store', 'core_store.id=sales_cart_item.store_id', ['store' => 'name'])
                ->join('warehouse', 'warehouse.id=sales_cart_item.warehouse_id', ['warehouse' => 'name'])
                ->where(['cart_id' => $this->getQuery('id')]);
        return $collection;
    }
}
