<?php

namespace Redseanet\Customer\Model\Wishlist;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Model\AbstractModel;

class Item extends AbstractModel
{
    protected function construct()
    {
        $this->init('wishlist_item', 'id', [
            'id', 'wishlist_id', 'product_id', 'product_name', 'warehouse_id',
            'store_id', 'qty', 'options', 'options_name', 'description', 'price', 'image'
        ]);
    }

    public function getProduct()
    {
        $product = new Product();
        $product->load($this->storage['product_id']);
        return $product;
    }

    public function afterRemove()
    {
        $this->flushList('wishlist');
        parent::afterRemove();
    }
}
