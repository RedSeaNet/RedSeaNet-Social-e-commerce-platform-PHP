<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Product;

use Redseanet\Customer\Model\Collection\Group;
use Redseanet\Lib\ViewModel\Template;

class TierPrice extends Template
{
    protected $template = 'admin/catalog/product/price/tier';

    public function getPrice()
    {
        $value = $this->getVariable('item')['value'];
        return $value ? json_decode($value, true) : [];
    }

    public function getGroups()
    {
        return new Group();
    }
}
