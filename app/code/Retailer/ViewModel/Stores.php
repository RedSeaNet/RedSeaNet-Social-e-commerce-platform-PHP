<?php

namespace Redseanet\Retailer\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Retailer\Model\Collection\Retailer as Collection;
use Redseanet\Lib\Model\Store;

class Stores extends Template
{
    use \Redseanet\Lib\Traits\Filter;

    public function getRetailers()
    {
        $query = $this->getQuery();
        $collection = new Collection();
        $collection->join('core_store', 'retailer.store_id=core_store.id', ['code', 'name'], 'left');
        $collection->where(['status' => 1]);
        if (!isset($query['asc']) && !isset($query['desc'])) {
            $collection->order('created_at DESC');
        }
        $this->filter($collection, $query);
        return $collection;
    }
}
