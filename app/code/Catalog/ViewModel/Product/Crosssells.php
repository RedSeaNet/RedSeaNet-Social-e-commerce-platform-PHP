<?php

namespace Redseanet\Catalog\ViewModel\Product;

class Crosssells extends Link
{
    public function getProducts()
    {
        $products = $this->getVariable('product')->getLinkedProducts('c');
        if ($this->getLimit() && is_object($products)) {
            $products->where(['status' => 1])->limit($this->getLimit());
        }
        return $products;
    }
}
