<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Product;

use Redseanet\Catalog\Model\Collection\Warehouse as Collection;

class Inventory extends Tab
{
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
