<?php

namespace Redseanet\Lib\Model\Eav\Attribute;

use Redseanet\Lib\Model\AbstractModel;

class Group extends AbstractModel
{
    protected function construct()
    {
        $this->init('eav_attribute_group', 'id', ['id', 'type_id', 'name']);
    }
}
