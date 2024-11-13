<?php

namespace Redseanet\Lib\Model\Collection\Eav\Attribute;

use Redseanet\Lib\Model\AbstractCollection;

class Group extends AbstractCollection
{
    protected function construct()
    {
        $this->init('eav_attribute_group');
    }
}
