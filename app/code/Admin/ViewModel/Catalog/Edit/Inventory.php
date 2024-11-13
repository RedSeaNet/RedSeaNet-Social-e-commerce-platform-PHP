<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit;

use Redseanet\Catalog\Model\Collection\Warehouse as Collection;
use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Catalog\Model\Product;

class Inventory extends Edit
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

    public function getWarehouses()
    {
        $collection = new Collection();
        $collection->columns(['id', 'name']);
        if ($id = $this->getProduct()->getId()) {
            $collection->join('warehouse_inventory', 'warehouse_inventory.warehouse_id=warehouse.id')
                    ->where(['product_id' => $id])
                    ->order('sku ASC');
        }
        return $collection->toArray();
    }
}
