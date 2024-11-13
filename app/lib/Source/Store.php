<?php

namespace Redseanet\Lib\Source;

use Redseanet\Lib\Model\Collection\Store as Collection;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class Store implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Collection();
        $collection->join('core_merchant', 'core_merchant.id=merchant_id', ['merchant' => 'code'], 'left');
        $collection->where(['core_store.status' => 1, 'core_merchant.status' => 1]);
        $result = [];
        foreach ($collection as $item) {
            if (!isset($result[$item['merchant']])) {
                $result[$item['merchant']] = [];
            }
            $result[$item['merchant']][$item['id']] = $item['name'];
        }
        return $result;
    }

    public function getNoneRetailerSourceArray()
    {
        $collection = new Collection();
        $collection->join('core_merchant', 'core_merchant.id=merchant_id', ['merchant' => 'code'], 'left');
        $collection->where(['core_store.status' => 1, 'core_merchant.status' => 1]);
        $restailer = new Select('retailer');
        $restailer->columns(['count' => new Expression('count(1)')])
                ->group('id')
        ->where->equalTo('store_id', 'core_store.id', 'identifier', 'identifier');
        $collection->columns(['*', 'code', 'retailer' => $restailer]);
        $result = [];
        foreach ($collection as $item) {
            if (!isset($result[$item['merchant']])) {
                $result[$item['merchant']] = [];
            }
            if ($item['retailer'] == 0) {
                $result[$item['merchant']][$item['id']] = $item['name'];
            }
        }
        return $result;
    }
}
