<?php

namespace Redseanet\Catalog\ViewModel\Product;

use Redseanet\Lib\ViewModel\Template;

class Price extends Template
{
    public function __construct()
    {
        $this->setTemplate('catalog/product/price');
    }

    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }
}
