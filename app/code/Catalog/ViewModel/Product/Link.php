<?php

namespace Redseanet\Catalog\ViewModel\Product;

use Redseanet\Catalog\ViewModel\Category\ProductList;

abstract class Link extends ProductList
{
    use \Redseanet\Lib\Traits\DB;

    protected $limit = null;

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }
}
