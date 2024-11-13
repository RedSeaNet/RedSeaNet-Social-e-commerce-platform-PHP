<?php

namespace Redseanet\Catalog\Source;

use Redseanet\Lib\Source\Eav\Attribute\Sortable as PSortable;

class Sortable extends PSortable
{
    use \Redseanet\Lib\Traits\Container,
        \Redseanet\Lib\Traits\Translate,
        \Redseanet\Lib\Traits\Url {
            translate as public;
        }

    protected $entityType = 'product';

    public function getSourceArray()
    {
        return ['default' => $this->translate('Default')] + parent::getSourceArray();
    }
}
