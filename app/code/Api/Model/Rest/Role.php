<?php

namespace Redseanet\Api\Model\Rest;

use Redseanet\Lib\Model\AbstractModel;

class Role extends AbstractModel
{
    protected function construct()
    {
        $this->init('api_rest_role', 'id', ['id', 'name', 'validation']);
    }
}
