<?php

namespace Redseanet\Retailer\Listeners;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Exception\ClickFarming;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Sales\Model\Cart;

class Farm implements ListenerInterface
{
    public function check($event)
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn')) {
            $retailer = new Retailer();
            $customer = $segment->get('customer');
            $retailer->load($customer['id'], 'customer_id');
            if (isset($event['product'])) {
                $product = $event['product'];
            } elseif (isset($event['product_id'])) {
                $product = new Product();
                $product->load($event['product_id']);
            }
            if (isset($product) && $retailer->getId() && $retailer->offsetGet('store_id') == $product->offsetGet('store_id')) {
                throw new ClickFarming('Click farming check failed. Retailer ID:' . $retailer->getId());
            }
        }
    }

    public function beforeCombine($event)
    {
        $customer = $event['model'];
        $retailer = new Retailer();
        $retailer->load($customer->getId(), 'customer_id');
        $cart = Cart::instance();
        foreach ($cart->getItems() as $item) {
            if ($retailer['store_id'] == $item['store_id']) {
                $cart->removeItem($item);
            }
        }
    }
}
