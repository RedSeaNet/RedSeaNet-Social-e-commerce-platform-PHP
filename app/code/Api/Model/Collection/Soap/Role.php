<?php

namespace Redseanet\Api\Model\Collection\Soap;

use Redseanet\Lib\Model\AbstractCollection;

class Role extends AbstractCollection
{
    protected function construct()
    {
        $this->init('api_soap_role');
    }
}
