<?php

namespace Redseanet\Checkout\ViewModel\Cart;

use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Catalog\ViewModel\Product\Link;
use Redseanet\Sales\Model\Cart;

class Crosssell extends Link
{
    protected $products = null;

    public function getProducts()
    {
        if (is_null($this->products)) {
            $ids = [];
            foreach (Cart::instance()->getItems() as $item) {
                $ids[] = $item['product_id'];
            }
            if (!count($ids)) {
                return [];
            }
            $tableGateway = $this->getTableGateway('product_link');
            $select = $tableGateway->getSql()->select();
            $select->columns(['linked_product_id'])
                    ->where
                    ->equalTo('type', 'c')
                    ->in('product_id', $ids)
                    ->notIn('linked_product_id', $ids);
            $result = $tableGateway->selectWith($select);
            $ids = [];
            foreach ($result as $item) {
                $ids[$item['linked_product_id']] = 1;
            }
            if (count($ids)) {
                $products = new Product();
                $products->where(['status' => 1])->where->in('id', array_keys($ids));
                $this->products = $products;
            } else {
                $this->products = [];
            }
        }
        return $this->products;
    }
}
