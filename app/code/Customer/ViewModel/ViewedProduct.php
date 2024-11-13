<?php

namespace Redseanet\Customer\ViewModel;

use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Log\Model\Collection\Visitor as Collection;

class ViewedProduct extends Template
{
    protected $products = null;

    public function getProducts()
    {
        if (is_null($this->products)) {
            $collection = new Collection();
            $segment = new Segment('customer');
            $collection->where(['customer_id' => $segment->get('customer')['id']])
                    ->order('id DESC')
            ->where->isNotNull('product_id');
            if ($collection->count()) {
                return $collection;
            }
        }
        return [];
    }
}
