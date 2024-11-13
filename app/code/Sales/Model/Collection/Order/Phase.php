<?php

namespace Redseanet\Sales\Model\Collection\Order;

use Redseanet\Lib\Model\AbstractCollection;

class Phase extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_order_phase');
    }
}
