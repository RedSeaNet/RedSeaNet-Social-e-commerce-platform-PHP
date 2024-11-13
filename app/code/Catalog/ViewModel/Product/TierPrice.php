<?php

namespace Redseanet\Catalog\ViewModel\Product;

class TierPrice extends View
{
    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }

    public function getPrices()
    {
        $final = $this->getProduct()->getFinalPrice(1, false);
        $tier = json_decode($this->getProduct()['tier_price'], true);
        $groups = [-1];
        if ($this->getSegment('customer')->get('hasLoggedIn')) {
            $customerArray = $this->getSegment('customer');
            $customer = new Customer();
            $customer->load($customerArray['id']);
            $groups = $customer->getGroup();
            foreach ($groups as $group) {
                $groups[] = $group['id'];
            }
        } else {
            $groups[] = 0;
        }
        $prices = [];
        foreach ($groups as $group) {
            if (isset($tier[$group])) {
                foreach ($tier[$group] as $qty => $price) {
                    if ($price >= $final) {
                        continue;
                    }
                    if (isset($prices[$qty])) {
                        $prices[$qty] = min($price, $prices[$qty]);
                    } else {
                        $prices[$qty] = $price;
                    }
                }
            }
        }
        return $prices;
    }
}
