<?php

namespace Redseanet\Catalog\Mq;

use Redseanet\Catalog\Exception\OutOfStock;
use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Catalog\Model\Product;

class Inventory implements MqInterface {

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

    public function decrease($data) {
        $warehouse = new Warehouse();
        $warehouse->load($data['warehouse_id']);
        foreach ($data["items"] as $item) {
            $this->check([
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $item['product_id'],
                'sku' => $item['sku'],
                'qty' => $item['qty'],
                'option_value_id_string' => $item['option_value_id_string']
            ]);
            $inventory = $warehouse->getInventory($item['product_id'], $item['option_value_id_string']);
            $inventory['qty'] = $inventory['qty'] - $item['qty'];
            $warehouse->setInventory($inventory);
            $product = new Product();
            $product->load($item['product_id']);
            if ($item['sku'] !== $product->offsetGet('sku')) {
                $inventory = $warehouse->getInventory($item['product_id'], $item['option_value_id_string']);
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
            $inventory = $warehouse->getInventory($item['product_id'], $item['sku']);
            $inventory['qty'] = $inventory['qty'] + $item['qty'];
            $inventory['id'] = null;
            $warehouse->setInventory($inventory);
        }
    }

}
