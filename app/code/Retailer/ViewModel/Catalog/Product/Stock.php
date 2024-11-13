<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Product;

use Redseanet\Catalog\Model\Collection\Product as Collection;
use Laminas\Db\Sql\Select;

class Stock extends AbstractProduct
{
    protected $actions = ['replenish', 'delete'];
    protected $messActions = ['replenish', 'delete'];

    public function delete($item = null)
    {
        return '<a data-method="post" href="' . $this->getBaseUrl('retailer/product/delete/') .
                ($item ? '" data-params="id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '"' :
                '" class="btn" data-serialize="#products-list"')
                . '>' . $this->translate('Delete') . '</a>';
    }

    public function replenish($item = null)
    {
        return '<a data-method="post" href="' . $this->getBaseUrl('retailer/product/replenish/') .
                ($item ? '" data-params="id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '"' :
                '" class="btn" data-serialize="#products-list"')
                . '>' . $this->translate('Replenish') . '</a>';
    }

    public function getProducts()
    {
        $collection = new Collection();
        $collection->where([
            'store_id' => $this->getRetailer()['store_id'],
            'status' => 1
        ])->order('id DESC');
        $stock = new Select('warehouse_inventory');
        $stock->columns(['product_id'])
                ->where(['status' => 0])
                ->group('product_id');
        $collection->in('id', $stock);
        $this->filter($collection);
        return $collection;
    }
}
