<?php

namespace Redseanet\Catalog\Model\Collection\Product;

use Redseanet\Lib\Model\AbstractCollection;

class Type extends AbstractCollection
{
    protected function construct()
    {
        $this->init('product_type');
    }
}
