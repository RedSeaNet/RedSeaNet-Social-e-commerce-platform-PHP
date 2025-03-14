<?php

namespace Redseanet\Catalog\Listeners;

use Redseanet\Catalog\Exception\OutOfStock;
use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Lib\Listeners\ListenerInterface;

class Inventory implements ListenerInterface {

    public function check($event) {
        $warehouse = new Warehouse();
        $warehouse->load($event['warehouse_id']);
        $inventory = $warehouse->getInventory($event['product_id'], $event['option_value_id_string']);
        $left = empty($inventory) ? 0 : $inventory['qty'] - $inventory['reserve_qty'];
        if (empty($inventory['status']) || $event['qty'] > $left) {
            throw new OutOfStock('There are only ' . $left .
                            ' left in stock. (Product SKU: ' . $event['sku'] . ')');
        }
    }

    public function decrease($event) {
        $model = $event['model'];
        $warehouse = new Warehouse();
        $warehouse->load($model['warehouse_id']);
        foreach ($model->getItems(true) as $item) {
            $this->check([
                'warehouse_id' => $model['warehouse_id'],
                'product_id' => $item['product_id'],
                'sku' => $item['sku'],
                'qty' => $item['qty'],
                'option_value_id_string' => $event['option_value_id_string'],
                'option_value_id' => $event['option_value_id']
            ]);
            $inventory = $warehouse->getInventory($item['product_id'], $event['option_value_id_string']);
            $inventory['qty'] = $inventory['qty'] - $item['qty'];
            $warehouse->setInventory($inventory);
            $product = $item->offsetGet('product');
            if ($item['sku'] !== $product->offsetGet('sku')) {
                $inventory = $warehouse->getInventory($item['product_id'], $event['option_value_id_string']);
                $inventory['qty'] = $inventory['qty'] - $item['qty'];
                $warehouse->setInventory($inventory);
            }
        }
    }

    public function increase($event) {
        $model = $event['model'];
        $warehouse = new Warehouse();
        $warehouse->load($model['warehouse_id']);
        foreach ($model->getItems(true) as $item) {
            $inventory = $warehouse->getInventory($item['product_id'], $item['option_value_id_string']);
            $inventory['qty'] = $inventory['qty'] + $item['qty'];
            $inventory['id'] = null;
            $warehouse->setInventory($inventory);
        }
    }

}
