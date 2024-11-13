<?php

namespace Redseanet\Retailer\Source;

use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Lib\Model\Collection\Store as Collection;

class Retailer implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Collection();
        $collection->join('core_merchant', 'core_merchant.id=merchant_id', ['merchant' => 'code'], 'left');
        $collection->join('retailer', 'retailer.store_id=core_store.id', ['retailer_id' => 'id'], 'left');
        $collection->where(['core_store.status' => 1, 'core_merchant.status' => 1]);
        $result = [];
        foreach ($collection as $item) {
            if (!isset($result[$item['merchant']])) {
                $result[$item['merchant']] = [];
            }
            $result[$item['merchant']][$item['retailer_id']] = $item['name'];
        }
        return $result;
    }
}
