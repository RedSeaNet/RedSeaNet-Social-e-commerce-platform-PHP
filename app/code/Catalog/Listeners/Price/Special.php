<?php

namespace Redseanet\Catalog\Listeners\Price;

class Special extends AbstractPrice
{
    public function calc($event)
    {
        $product = $event['product'];
        $now = time();
        $start = (!empty($product['special_price_start']) ? strtotime($product['special_price_start']) : time());
        $end = (!empty($product['special_price_end']) ? strtotime($product['special_price_end']) : time());
        $price = (float) $event['product']['special_price'];
        if ($price && (!$start || $now >= $start) && (!$end || $now <= $end)) {
            $product['base_prices']['special'] = $price;
            $product['prices']['special'] = $this->getCurrency()->convert($price);
        }
    }
}
