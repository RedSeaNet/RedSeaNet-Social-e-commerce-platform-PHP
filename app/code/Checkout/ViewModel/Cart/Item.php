<?php

namespace Redseanet\Checkout\ViewModel\Cart;

use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Session\Segment;

class Item extends Template
{
    protected static $warehouses = [];

    public function canSold($item)
    {
        if (!isset(self::$warehouses[$item['warehouse_id']])) {
            self::$warehouses[$item['warehouse_id']] = new Warehouse();
            self::$warehouses[$item['warehouse_id']]->load($item['warehouse_id']);
        }
        $product = $item['product'];
        $inventory = self::$warehouses[$item['warehouse_id']]->getInventory($product->getId(), $item['option_value_id_string']);
        return $product->canSold() && isset($inventory['status']) && $inventory['status'] &&
                $inventory['qty'] > $inventory['reserve_qty'] &&
                min((float) $inventory['max_qty'], (float) $inventory['qty']) > (float) $inventory['min_qty'];
    }

    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }

    public function getCustomer()
    {
        $segment = new Segment('customer');
        $customer = new Customer();
        $customer->load($segment->get('customer')['id'], 'id');
        return $customer;
    }
}
