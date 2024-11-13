<?php

namespace Redseanet\Sales\Model\Collection\Invoice;

use Redseanet\Lib\Model\AbstractCollection;

class Item extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_order_invoice_item');
    }
}
