<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Product;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\ViewModel\Template;

class Tab extends Template
{
    private static $product;

    public function getProduct()
    {
        self::$product = new Product();
        if ($this->getQuery('id')) {
            self::$product->load($this->getQuery('id'));
        }
        return self::$product;
    }
}
