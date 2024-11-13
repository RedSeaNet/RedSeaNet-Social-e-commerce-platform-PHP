<?php

namespace Redseanet\Promotion\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Coupon extends AbstractCollection
{
    protected function construct()
    {
        $this->init('promotion_coupon');
    }
}
