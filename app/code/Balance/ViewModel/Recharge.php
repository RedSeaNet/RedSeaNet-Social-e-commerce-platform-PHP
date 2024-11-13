<?php

namespace Redseanet\Balance\ViewModel;

use Redseanet\Customer\ViewModel\Account;
use Redseanet\Catalog\Model\Product;

class Recharge extends Account
{
    protected static $product_type = null;

    public function getProduct()
    {
        $product = new Product();
        $product->product_type = 2;
    }
}
