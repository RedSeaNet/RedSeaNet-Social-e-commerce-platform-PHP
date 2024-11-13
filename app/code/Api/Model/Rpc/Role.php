<?php

namespace Redseanet\Api\Model\Rpc;

use Redseanet\Lib\Model\AbstractModel;

class Role extends AbstractModel
{
    protected $role = null;

    protected function construct()
    {
        $this->init('api_rpc_role', 'id', ['id', 'name']);
    }

    public function getPermission()
    {
        $result = [];
        if ($this->getId()) {
            $result = $this->fetchRow($this->getId(), null, 'api_rpc_permission');
            if (!is_array($result) && empty($result)) {
                $tableGateway = $this->getTableGateway('api_rpc_permission');
                $resultSet = $tableGateway->select(['role_id' => $this->getId(), 'permission' => 1])->toArray();
                array_walk($resultSet, function ($item) use (&$result) {
                    $result[] = $item['resource'];
                });
                $this->flushRow($this->getId(), $result, 'api_rpc_permission');
            }
        }
        return $result;
    }

    public function hasPermission($name)
    {
        return in_array($name, $this->getPermission());
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if (!empty($this->storage['resource'])) {
            $tableGateway = $this->getTableGateway('api_rpc_permission');
            $tableGateway->delete(['role_id' => $this->getId()]);
            foreach ($this->storage['resource'] as $resource) {
                $tableGateway->insert(['role_id' => $this->getId(), 'resource' => $resource]);
            }
        }
        $this->flushRow($this->getId(), null, 'api_rpc_permission');
        parent::afterSave();
        $this->commit();
    }
}
