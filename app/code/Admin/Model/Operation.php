<?php

namespace Redseanet\Admin\Model;

use Redseanet\Lib\Model\AbstractModel;

class Operation extends AbstractModel
{
    protected $role = null;

    protected function construct()
    {
        $this->init('admin_operation', 'id', ['id', 'name', 'description', 'is_system']);
    }
}
