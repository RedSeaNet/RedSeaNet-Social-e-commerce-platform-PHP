<?php

namespace Redseanet\Catalog\Listeners\Price;

class Regular extends AbstractPrice
{
    public function calc($event)
    {
        $event['product']['base_prices']['regular'] = $event['product']['price'];
        $event['product']['prices']['regular'] = $this->getCurrency()->convert($event['product']['price']);
    }
}
