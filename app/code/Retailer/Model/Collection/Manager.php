<?php

namespace Redseanet\Retailer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Manager extends AbstractCollection
{
    protected function construct()
    {
        $this->init('retailer_manager');
    }
}
