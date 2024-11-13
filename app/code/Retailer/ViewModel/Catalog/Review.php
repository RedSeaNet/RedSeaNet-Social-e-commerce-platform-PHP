<?php

namespace Redseanet\Retailer\ViewModel\Catalog;

use Redseanet\Catalog\Model\Collection\Product\Review as Collection;
use Redseanet\Retailer\ViewModel\AbstractViewModel;

class Review extends AbstractViewModel
{
    use \Redseanet\Lib\Traits\Filter;

    public function getReviews()
    {
        $collection = new Collection();
        $collection->join('product_entity', 'product_entity.id=review.product_id', [], 'left')
                ->where(['product_entity.store_id' => $this->getRetailer()->offsetGet('store_id')])
                ->order('created_at DESC');
        $condition = $this->getQuery();
        if (!empty($condition['status'])) {
            $collection->where(['reply' => null]);
        }
        unset($condition['status']);
        $this->filter($collection, $condition, ['order' => 1]);
        return $collection;
    }

    public function getFilterUrl($condition = [])
    {
        $uri = $this->getUri();
        $query = $this->getQuery();
        foreach ($condition as $key => $value) {
            if (is_null($value)) {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }
        return $uri->withFragment('')->withQuery(http_build_query($query));
    }
}
