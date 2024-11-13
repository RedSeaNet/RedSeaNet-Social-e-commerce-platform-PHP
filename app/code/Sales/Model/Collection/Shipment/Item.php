<?php

namespace Redseanet\Sales\Model\Collection\Shipment;

use Redseanet\Lib\Model\AbstractCollection;

class Item extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_order_shipment_item');
    }
}
