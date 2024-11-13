<?php

namespace Redseanet\Sales\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Order extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_order');
    }
}
