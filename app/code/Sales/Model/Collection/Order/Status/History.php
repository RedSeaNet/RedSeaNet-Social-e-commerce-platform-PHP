<?php

namespace Redseanet\Sales\Model\Collection\Order\Status;

use Redseanet\Lib\Model\AbstractCollection;

class History extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_order_status_history');
    }
}
