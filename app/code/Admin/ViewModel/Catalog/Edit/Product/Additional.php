<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Product;

class Additional extends Tab
{
    public function getAttributes()
    {
        if ($this->getProduct()->offsetGet('additional')) {
            return json_decode($this->getProduct()->offsetGet('additional'), true);
        } else {
            return [];
        }
    }
}
