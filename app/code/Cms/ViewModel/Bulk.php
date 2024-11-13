<?php

namespace Redseanet\Cms\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Catalog\ViewModel\Product\Price;

class Bulk extends Template
{
    protected $product = null;

    public function __construct()
    {
        $this->setTemplate('cms/bulk');
    }

    public function getBulkProducts($limit = 8)
    {
        if (is_null($this->product)) {
            $collection = new Product();
            $collection->where("bulk_price!=''");
            $collection->limit($limit);
            $this->product = $collection->load();
        }
        return $this->product;
    }

    public function getPriceBox($product)
    {
        $box = new Price();
        $box->setVariable('product', $product);
        return $box;
    }
}
