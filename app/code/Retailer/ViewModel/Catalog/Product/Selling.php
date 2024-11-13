<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Product;

use Redseanet\Catalog\Model\Collection\Product as Collection;
use Laminas\Db\Sql\Select;

class Selling extends AbstractProduct
{
    protected $actions = ['withdraw', 'delete', 'recommend', 'bulk'];
    protected $messActions = ['withdraw', 'delete', 'recommend', 'cancelRecommendation'];

    public function withdraw($item = null)
    {
        return '<a data-method="post" href="' . $this->getBaseUrl('retailer/product/withdraw/') .
                ($item ? '" data-params="id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '"' :
                '" class="btn" data-serialize="#products-list"')
                . '>' . $this->translate('Withdraw') . '</a>';
    }

    public function delete($item = null)
    {
        return '<a data-method="post" href="' . $this->getBaseUrl('retailer/product/delete/') .
                ($item ? '" data-params="id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '"' :
                '" class="btn" data-serialize="#products-list"')
                . '>' . $this->translate('Delete') . '</a>';
    }

    public function recommend($item = null)
    {
        if ($item && $item['recommended'] == 1) {
            return ('<a data-method="post" href="' . $this->getBaseUrl('retailer/product/cancelRecommend/') .
                    ($item ? '" data-params="id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '"' :
                    '" class="btn" data-serialize="#products-list"')
                    . '>' . $this->translate('Cancel Recommendation') . '</a>');
        } else {
            return ('<a data-method="post" href="' . $this->getBaseUrl('retailer/product/recommend/') .
                    ($item ? '" data-params="id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '"' :
                    '" class="btn" data-serialize="#products-list"')
                    . '>' . $this->translate('Recommend') . '</a>');
        }
    }

    public function cancelRecommendation($item = null)
    {
        return !$item || $item['recommended'] == 1 ? ('<a data-method="post" href="' . $this->getBaseUrl('retailer/product/cancelRecommend/') .
                ($item ? '" data-params="id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '"' :
                '" class="btn" data-serialize="#products-list"')
                . '>' . $this->translate('Cancel Recommendation') . '</a>') : '';
    }

    public function bulk($item = null)
    {
        return '<a href="' . $this->getBaseUrl('bulk/price/edit/?id=') . $item['id'] . '">' . $this->translate('Bulk Sale Configuration') . '</a>';
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
                ->where(['status' => 1])
                ->group('product_id');
        $collection->in('id', $stock);
        $this->filter($collection);
        return $collection;
    }
}
