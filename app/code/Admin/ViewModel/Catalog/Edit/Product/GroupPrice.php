<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Product;

use Redseanet\Customer\Model\Collection\Group;
use Redseanet\Lib\ViewModel\Template;

class GroupPrice extends Template
{
    protected $template = 'admin/catalog/product/price/group';

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
