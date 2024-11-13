<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Edit\Product;

class StoreCategory extends Tab
{
    protected $activeIds = null;

    public function getActiveIds()
    {
        if (is_null($this->activeIds)) {
            $this->activeIds = (array) $this->getProduct()['store_category'];
        }
        return $this->activeIds;
    }
}
