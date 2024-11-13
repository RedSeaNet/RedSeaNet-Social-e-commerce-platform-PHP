<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Product;

use Redseanet\Catalog\Model\Collection\Product as Collection;

class History extends AbstractProduct
{
    protected $actions = ['resell'];
    protected $messActions = ['resell'];

    public function resell($item = null)
    {
        return '<a data-method="post" href="' . $this->getBaseUrl('retailer/product/resell/') .
                ($item ? '" data-params="id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '"' :
                '" class="btn" data-serialize="#products-list"')
                . '>' . $this->translate('Resell') . '</a>';
    }

    public function getProducts()
    {
        $collection = new Collection();
        $collection->where([
            'store_id' => $this->getRetailer()['store_id'],
            'status' => 0
        ])->order('id DESC');
        $this->filter($collection);
        return $collection;
    }
}
