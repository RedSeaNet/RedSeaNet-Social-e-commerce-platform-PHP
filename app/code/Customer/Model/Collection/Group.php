<?php

namespace Redseanet\Customer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Group extends AbstractCollection
{
    protected function construct()
    {
        $this->init('customer_group');
    }
}
