<?php

namespace Redseanet\Admin\Model;

use Redseanet\Admin\Model\Collection\Role as Collection;
use Redseanet\Lib\Model\AbstractModel;
use Laminas\Permissions\Rbac\Role as RbacRole;

class Role extends AbstractModel
{
    protected $role = null;

    protected function construct()
    {
        $this->init('admin_role', 'id', ['id', 'name', 'status']);
    }

    public function hasPermission($name)
    {
        return !is_null($this->role) && $this->role->hasPermission('ALL') || $this->role->hasPermission($name);
    }

    protected function afterLoad(&$result)
    {
        parent::afterLoad($result);
        $cache = $this->getContainer()->get('cache');
        $this->role = $cache->fetch($this->offsetGet('name'), 'RBAC_ROLE_');
        if (!$this->role) {
            $this->role = new RbacRole($this->offsetGet('name'));
            $roles = new Collection();
            $roles->addChildren()->addOperation()->load();
            $children = [];
            foreach ($roles as $role) {
                if ($role['id'] == $this->getId()) {
                    foreach ($role['operation'] as $operation) {
                        $this->role->addPermission($operation);
                    }
                } else {
                    $children[$role['id']] = $role['child_id'];
                }
            }
            $this->addChildren($children, $this->role, $this->getId());
            $cache->save($this->offsetGet('name'), $this->role, 'RBAC_ROLE_');
        }
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if (!empty($this->storage['child_id'])) {
            $tableGateway = $this->getTableGateway('admin_role_recursive');
            $tableGateway->delete(['role_id' => $this->getId()]);
            foreach ($this->storage['child_id'] as $childId) {
                $tableGateway->insert(['role_id' => $this->getId(), 'child_id' => $childId]);
            }
        }
        if (!empty($this->storage['operation_id'])) {
            $tableGateway = $this->getTableGateway('admin_permission');
            $tableGateway->delete(['role_id' => $this->getId()]);
            if (in_array(-1, $this->storage['operation_id'])) {
                $this->storage['operation_id'] = [-1];
            }
            $unique = [];
            foreach ($this->storage['operation_id'] as $operationId) {
                if ($operationId && !isset($unique[$operationId])) {
                    $unique[$operationId] = 1;
                    $tableGateway->insert(['role_id' => $this->getId(), 'operation_id' => $operationId]);
                }
            }
        }
        $this->flushList('admin_operation');
        $this->getContainer()->get('cache')->delete($this->storage['name'], 'RBAC_ROLE_');
        parent::afterSave();
        $this->commit();
    }

    protected function addChildren($children, $parent, $pid)
    {
        if (isset($children[$pid])) {
            foreach ($children[$pid] as $child) {
                $role = new RbacRole($child['name']);
                foreach ($child['operation'] as $operation) {
                    $role->addPermission($operation);
                }
                $parent->addChild($this->addChildren($children, $role, $role['id']));
            }
        }
        return $parent;
    }

    public function getChildren()
    {
        $roles = new Collection();
        $roles->addChildren()->where([$this->primaryKey => $this->getId()]);
        $result = [];
        foreach ($roles as $role) {
            $result[] = $role['child_id'];
        }
        return $result;
    }
}
