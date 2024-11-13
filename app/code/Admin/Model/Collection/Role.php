<?php

namespace Redseanet\Admin\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Role extends AbstractCollection
{
    public $i = 0;

    protected function construct()
    {
        $this->init('admin_role');
    }

    public function addOperation()
    {
        $this->select->join('admin_permission', 'admin_permission.role_id = admin_role.id', [], 'left');
        $this->select->join('admin_operation', 'admin_permission.operation_id = admin_operation.id', ['operation' => 'name'], 'left');
        $this->select->where(['permission' => 1]);
        return $this;
    }

    public function addChildren()
    {
        $this->select->join('admin_role_recursive', 'admin_role_recursive.role_id = admin_role.id', ['child_id'], 'left');
        return $this;
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['operation']) || isset($result[0]['child_id'])) {
            $storage = [];
            foreach ($result as $item) {
                if (isset($storage[$item['id']])) {
                    if (isset($item['operation'])) {
                        $storage[$item['id']]['operation'][] = $item['operation'];
                    }
                    if (isset($item['child_id'])) {
                        $storage[$item['id']]['children'][] = $item['child_id'];
                    }
                } else {
                    $storage[$item['id']] = $item;
                    if (isset($item['operation'])) {
                        $storage[$item['id']]['operation'] = [$item['operation']];
                    }
                    if (isset($item['child_id'])) {
                        $storage[$item['id']]['children'] = [$item['child_id']];
                    }
                }
            }
            foreach ($storage as $item) {
                $children = [];
                if (!isset($item['children'])) {
                    break;
                }
                foreach ($item['children'] as $child) {
                    if (isset($stroage[$child])) {
                        $children[] = &$stroage[$child];
                    }
                }
                $item['children'] = $children;
            }
            $result = array_values($storage);
        }
        parent::afterLoad($result);
    }
}
