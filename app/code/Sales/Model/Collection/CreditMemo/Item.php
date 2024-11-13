<?php

namespace Redseanet\Sales\Model\Collection\CreditMemo;

use Redseanet\Lib\Model\AbstractCollection;

class Item extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_order_creditmemo_item');
    }
}
