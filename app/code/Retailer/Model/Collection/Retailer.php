<?php

namespace Redseanet\Retailer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Retailer extends AbstractCollection
{
    protected function construct()
    {
        $this->init('retailer');
    }
}
