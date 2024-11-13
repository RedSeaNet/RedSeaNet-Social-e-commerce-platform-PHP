<?php

namespace Redseanet\Sales\Model\Collection\Rma;

use Redseanet\Lib\Model\AbstractCollection;

class Item extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_rma_item');
    }
}
