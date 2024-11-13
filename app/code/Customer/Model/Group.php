<?php

namespace Redseanet\Customer\Model;

use Redseanet\Lib\Model\AbstractModel;

class Group extends AbstractModel
{
    protected function construct()
    {
        $this->init('customer_group', 'id', ['id', 'name']);
    }
}
