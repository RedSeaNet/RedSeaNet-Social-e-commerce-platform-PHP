<?php

namespace Redseanet\Retailer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class ProductInCategoryCollection extends AbstractCollection
{
    protected function construct()
    {
        $this->init('retailer_category_with_product', 'id', ['id', 'product_id', 'category_id']);
    }
}
