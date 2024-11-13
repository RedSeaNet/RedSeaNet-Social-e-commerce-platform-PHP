<?php

namespace Redseanet\Catalog\Listeners\Price;

use Redseanet\Lib\Session\Segment;
use Redseanet\Customer\Model\Customer;

class Group extends AbstractPrice
{
    public function calc($event)
    {
        $product = $event['product'];
        $segment = new Segment('customer');
        if ($product['group_price'] && ($price = json_decode($product['group_price'], true))) {
            $prices = [];
            $groups = [['id' => 0]];
            if ($segment->get('hasLoggedIn')) {
                $customerArray = $segment->get('customer');
                $customer = new Customer();
                $customer->load($customerArray['id']);
                $groups = $customer->getGroup();
            }
            foreach ($groups as $group) {
                if (isset($price[$group['id']])) {
                    $prices[] = $price[$group['id']];
                }
            }
            if ($prices) {
                $product['base_prices']['group'] = min($prices);
                $product['prices']['group'] = $this->getCurrency()->convert($product['base_prices']['group']);
            }
        }
    }
}
