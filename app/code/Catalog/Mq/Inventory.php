<?php

namespace Redseanet\Catalog\Mq;

use Redseanet\Catalog\Exception\OutOfStock;
use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Sales\Model\Order;

class Inventory implements MqInterface
{
    public function check($event)
    {
        $warehouse = new Warehouse();
        $warehouse->load($event['warehouse_id']);
        $inventory = $warehouse->getInventory($event['product_id'], $event['sku']);
        $left = empty($inventory) ? 0 : $inventory['qty'] - $inventory['reserve_qty'];
        if (empty($inventory['status']) || $event['qty'] > $left) {
            throw new OutOfStock('There are only ' . $left .
                    ' left in stock. (Product SKU: ' . $event['sku'] . ')');
        }
    }

    public function decrease($data)
    {
        for ($i = 0; $i < count($data['ids']); $i++) {
            $model = new Order();
            $model->load($data['ids'][$i]);
            $warehouse = new Warehouse();
            $warehouse->load($model['warehouse_id']);
            foreach ($model->getItems(true) as $item) {
                $this->check([
                    'warehouse_id' => $model['warehouse_id'],
                    'product_id' => $item['product_id'],
                    'sku' => $item['sku'],
                    'qty' => $item['qty']
                ]);
                $inventory = $warehouse->getInventory($item['product_id'], $item['sku']);
                $inventory['qty'] = $inventory['qty'] - $item['qty'];
                $warehouse->setInventory($inventory);
                $product = $item->offsetGet('product');
                if ($item['sku'] !== $product->offsetGet('sku')) {
                    $inventory = $warehouse->getInventory($item['product_id'], $product->offsetGet('sku'));
                    $inventory['qty'] = $inventory['qty'] - $item['qty'];
                    $warehouse->setInventory($inventory);
                }
            }
        }
    }

    public function increase($event)
    {
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
