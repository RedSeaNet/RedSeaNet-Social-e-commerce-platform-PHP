<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Edit\Product;

class Additional extends Tab
{
    public function getAttributes()
    {
        $additional = $this->getProduct()->offsetGet('additional');
        return !empty($additional) ? json_decode($additional, true) : [];
    }
}
