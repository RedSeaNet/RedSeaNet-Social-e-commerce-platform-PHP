<?php

namespace Redseanet\Catalog\Model;

use Redseanet\Catalog\Model\Collection\Warehouse\Inventory;
use Redseanet\Lib\Model\AbstractModel;

class Warehouse extends AbstractModel
{
    protected function construct()
    {
        $this->init('warehouse', 'id', ['name', 'country', 'region', 'city', 'address', 'contact_info', 'longitude', 'latitude', 'open_at', 'close_at', 'status']);
    }

    public function getInventory($productId, $sku = '')
    {
        if ($this->getId()) {
            $inventory = new Inventory();
            $constraint = [
                'warehouse_id' => $this->getId(),
                'product_id' => $productId
            ];
            if ($sku) {
                $constraint['sku'] = $sku;
            }
            $inventory->where($constraint);
            $inventory->load(true, true);
            if (!$sku && count($inventory)) {
                $count = 0;
                foreach ($inventory as $item) {
                    $count += $item['qty'];
                }
                return ['qty' => $count] + $inventory[0];
            }
            return count($inventory) ? $inventory[0] : [];
        }
        return null;
    }

    public function setInventory(array $inventory)
    {
        if ($this->getId() || isset($inventory['warehouse_id'])) {
            $tableGateway = $this->getTableGateway('warehouse_inventory');
            $constraint = [
                'warehouse_id' => $this->getId() ?: $inventory['warehouse_id'],
                'product_id' => $inventory['product_id'],
                'sku' => $inventory['sku']
            ];
            $this->upsert(array_intersect_key($inventory, [
                'barcode' => 1, 'qty' => 1, 'reserve_qty' => 1,
                'min_qty' => 1, 'max_qty' => 1, 'is_decimal' => 1,
                'backorders' => 1, 'increment' => 1, 'status' => 1
            ]), $constraint, $tableGateway);
            $this->flushList('warehouse_inventory');
            $this->flushList('warehouse');
        }
        return $this;
    }
}
