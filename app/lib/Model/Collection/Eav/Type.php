<?php

namespace Redseanet\Lib\Model\Collection\Eav;

use Redseanet\Lib\Model\AbstractCollection;

class Type extends AbstractCollection
{
    protected function construct()
    {
        $this->init('eav_entity_type');
    }
}
