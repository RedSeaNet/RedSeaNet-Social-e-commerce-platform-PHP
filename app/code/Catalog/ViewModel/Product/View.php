<?php

namespace Redseanet\Catalog\ViewModel\Product;

use Redseanet\Lib\ViewModel\Template;

class View extends Template
{
    protected static $product = null;

    public function getProduct()
    {
        return self::$product;
    }

    public function setProduct($product)
    {
        self::$product = $product;
        return $this;
    }

    public function getPriceBox()
    {
        $box = new Price();
        $box->setVariable('product', self::$product);
        return $box;
    }
}
