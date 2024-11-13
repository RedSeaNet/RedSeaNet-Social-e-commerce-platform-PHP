<?php

namespace Redseanet\Admin\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Operation extends AbstractCollection
{
    protected function construct()
    {
        $this->init('admin_operation');
    }

    public function orderByRole()
    {
        $this->select->join('admin_permission', 'admin_permission.operation_id = admin_operation.id', [], 'left')
                ->join('admin_role', 'admin_permission.role_id = admin_role.id', ['role' => 'name', 'role_id' => 'id'], 'left')
                ->order('admin_operation.name');
        return $this;
    }
}
