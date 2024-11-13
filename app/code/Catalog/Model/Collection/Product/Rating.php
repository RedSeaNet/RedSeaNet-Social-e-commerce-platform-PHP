<?php

namespace Redseanet\Catalog\Model\Collection\Product;

use Redseanet\Lib\Model\AbstractCollection;

class Rating extends AbstractCollection
{
    protected function construct()
    {
        $this->init('rating');
    }
}
