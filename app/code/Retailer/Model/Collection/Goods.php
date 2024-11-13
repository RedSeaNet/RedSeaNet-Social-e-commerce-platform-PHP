<?php

namespace Redseanet\Retailer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Product extends AbstractCollection
{
    protected function construct()
    {
        //$this->init('retailer', 'id', ['id', 'customer_id', 'store_id', 'name', 'address', 'account', 'photo', 'credentials', 'status']);
        $this->init('retailer');
    }

    /**
     * getRetailerTransaction
     * Fetch transaction record form database
     *
     * @access public
     * @return object
     */
    public function getRetailerTransaction()
    {
        $this->select->join('sales_order', 'retailer.store_id = sales_order.store_id', [], 'left');
        $this->select->order('sales_order.created_at DESC');
        return $this;
    }
}
