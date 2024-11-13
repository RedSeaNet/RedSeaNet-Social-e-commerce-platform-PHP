<?php

namespace Redseanet\Retailer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Category extends AbstractCollection
{
    protected function construct()
    {
        $this->init('retailer_category');
    }
}
