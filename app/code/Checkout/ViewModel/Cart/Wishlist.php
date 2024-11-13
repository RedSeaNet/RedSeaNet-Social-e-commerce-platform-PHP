<?php

namespace Redseanet\Checkout\ViewModel\Cart;

use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Catalog\ViewModel\Product\Link;
use Redseanet\Customer\Model\Collection\Wishlist\Item as WishlistItem;
use Redseanet\Lib\Session\Segment;

class Wishlist extends Link
{
    public function getProducts()
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn')) {
            $customer = $segment->get('customer');
            $items = new WishlistItem();
            $items->join('wishlist', 'wishlist.id=wishlist_item.wishlist_id', [], 'left')
                    ->columns(['product_id'])
                    ->where(['wishlist.customer_id' => $customer['id']])
            ->where->isNotNull('product_id');
            $items->load(true, true);
            $ids = [];
            foreach ($items as $item) {
                $ids[] = $item['product_id'];
            }
            if ($ids) {
                $products = new Product();
                $products->where(['status' => 1])->where->in('id', $ids);
                return $products;
            }
        }
        return [];
    }
}
