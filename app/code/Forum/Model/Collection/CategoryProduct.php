<?php

namespace Redseanet\Forum\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class CategoryProduct extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_product_relation');
    }
}
