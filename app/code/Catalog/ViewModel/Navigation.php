<?php

namespace Redseanet\Catalog\ViewModel;

use Redseanet\Catalog\Model\Collection\Category;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Model\Collection\Store as storeCollection;

class Navigation extends Template
{
    public function getRootCategory()
    {
        $categories = new Category();
        $categories->where(['parent_id' => null]);
        if (count($categories)) {
            return $categories[0];
        }
        return [];
    }

    public function getRecomentStore()
    {
        $stores = new storeCollection();
        $stores->join('retailer', 'core_store.id=retailer.store_id', ['uri_key'], 'left');
        $stores->where(['core_store.status' => 1]);
        $stores->limit(8);
        return $stores;
    }
}
