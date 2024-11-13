<?php

namespace Redseanet\Sales\Model\Collection\Cart;

use Redseanet\Lib\Model\AbstractCollection;

class Item extends AbstractCollection
{
    protected $cacheLifeTime = 43200;

    protected function construct()
    {
        $this->init('sales_cart_item');
    }
}
