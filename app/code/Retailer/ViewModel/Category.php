<?php

namespace Redseanet\Retailer\ViewModel;

use Redseanet\Retailer\Model\Collection\Category as Collection;

class Category extends AbstractViewModel
{
    public function getCategories()
    {
        $collection = new Collection();
        $collection->where(['store_id' => $this->getRetailer()['store_id']])
                ->order('sort_order');
        $tree = [];
        $collection->walk(function ($item) use (&$tree) {
            if (!isset($tree[(int) $item['parent_id']])) {
                $tree[(int) $item['parent_id']] = [$item];
            } else {
                $tree[(int) $item['parent_id']][] = $item;
            }
        });
        return $tree;
    }
}
