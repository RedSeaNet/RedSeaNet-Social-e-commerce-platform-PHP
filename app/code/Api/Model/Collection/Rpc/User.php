<?php

namespace Redseanet\Api\Model\Collection\Rpc;

use Redseanet\Lib\Model\AbstractCollection;

class User extends AbstractCollection
{
    protected function construct()
    {
        $this->init('api_rpc_user');
    }
}
