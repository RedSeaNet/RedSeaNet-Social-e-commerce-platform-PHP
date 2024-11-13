<?php

namespace Redseanet\Lib\Model\Eav;

use Redseanet\Lib\Model\AbstractModel;

class Type extends AbstractModel
{
    protected function construct()
    {
        $this->init('eav_entity_type', 'id', ['id', 'code', 'entity_table', 'value_table_prefix', 'is_form']);
    }
}
