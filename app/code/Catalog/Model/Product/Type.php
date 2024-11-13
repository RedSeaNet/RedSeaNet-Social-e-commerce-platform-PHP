<?php

namespace Redseanet\Catalog\Model\Product;

use Redseanet\Lib\Model\AbstractModel;

class Type extends AbstractModel
{
    protected function construct()
    {
        $this->init('product_type', 'id', ['id', 'code', 'name']);
    }
}
