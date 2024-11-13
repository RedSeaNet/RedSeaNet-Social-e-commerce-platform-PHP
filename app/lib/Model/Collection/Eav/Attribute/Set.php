<?php

namespace Redseanet\Lib\Model\Collection\Eav\Attribute;

use Redseanet\Lib\Model\AbstractCollection;

class Set extends AbstractCollection
{
    protected function construct()
    {
        $this->init('eav_attribute_set');
    }
}
