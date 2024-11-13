<?php

namespace Redseanet\Resource\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

/**
 * System backend Resource category
 */
class Resource extends AbstractCollection
{
    protected function construct()
    {
        $this->init('resource');
    }
}
