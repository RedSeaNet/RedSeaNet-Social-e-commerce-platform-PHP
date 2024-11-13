<?php

namespace Redseanet\Retailer\Source;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Retailer\Model\Collection\Category as Collection;
use Redseanet\Lib\Tool\PHPTree;

class Category implements SourceInterface
{
    public function getSourceArray($storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = Bootstrap::getStore()->getId();
        }
        $collection = new Collection();
        $collection->where([
            'store_id' => $storeId
        ])->order('parent_id ASC, id ASC');
        $result = [];
        $collection->walk(function ($item) use (&$result) {
            $result[$item['id']] = $item->getName();
        });
        return $result;
    }

    public function getSourceArrayTree($storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = Bootstrap::getStore()->getId();
        }
        $collection = new Collection();
        $collection->where([
            'store_id' => $storeId
        ])->order('parent_id ASC, id ASC');
        $result = [];
        $collection->walk(function ($category) use (&$result) {
            $result[] = ['id' => $category['id'], 'name' => $category->getName(), 'parent_id' => (isset($category['parent_id']) ? $category['parent_id'] : 0)];
        });
        return PHPTree::makeTreeForHtml($result);
    }
}
