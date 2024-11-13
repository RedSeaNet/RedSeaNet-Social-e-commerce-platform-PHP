<?php

namespace Redseanet\Catalog\Model\Collection\Warehouse;

use Redseanet\Lib\Model\AbstractCollection;

class Inventory extends AbstractCollection
{
    protected function construct()
    {
        $this->init('warehouse_inventory');
    }

    protected function afterLoad(&$result)
    {
        foreach ($result as $key => $item) {
            if (isset($item['warehouse_id'])) {
                $idStr = base64_encode(json_encode(['warehouse_id' => $item['warehouse_id'], 'product_id' => $item['product_id'], 'sku' => $item['sku']]));
                $result[$key]['id'] = $idStr;
            } else {
                $result[$key]['id'] = '';
            }
        }
        parent::afterLoad($result);
    }
}
