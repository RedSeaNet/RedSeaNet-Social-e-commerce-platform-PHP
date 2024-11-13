<?php

namespace Redseanet\Catalog\ViewModel\Product;

class Media extends Link
{
    public function getProducts()
    {
        $products = $this->getVariable('product')->getLinkedProducts('m');

        if ($this->getLimit()) {
            $products->limit($this->getLimit());
        }
        return $products;
    }
}
