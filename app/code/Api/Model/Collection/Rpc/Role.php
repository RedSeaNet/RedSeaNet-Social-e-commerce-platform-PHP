<?php

namespace Redseanet\Api\Model\Collection\Rpc;

use Redseanet\Lib\Model\AbstractCollection;

class Role extends AbstractCollection
{
    protected function construct()
    {
        $this->init('api_rpc_role');
    }
}
