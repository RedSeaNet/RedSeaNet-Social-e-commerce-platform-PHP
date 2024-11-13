<?php

namespace Redseanet\Sales\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Invoice extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_order_invoice');
    }
}
