<?php

namespace Redseanet\Sales\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Cart extends AbstractCollection
{
    protected $arrayMode = true;
    protected $cacheLifeTime = 43200;

    protected function construct()
    {
        $this->init('sales_cart');
    }
}
