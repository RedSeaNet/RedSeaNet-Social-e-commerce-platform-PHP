<?php

namespace Redseanet\Admin\Controller\Api\Rest;

use Redseanet\Api\Model\Rest\Role as Model;
use Redseanet\Lib\Controller\AuthActionController;

class RoleController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_rest_role_list');
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_rest_role_edit');
        $model = new Model();
        if ($id = $this->getRequest()->getQuery('id')) {
            $model->load($id);
            $root->getChild('head')->setTitle('Edit Role / REST');
        } else {
            $root->getChild('head')->setTitle('Add New Role / REST');
        }
        $root->getChild('edit', true)->setVariable('model', $model);
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Api\\Model\\Rest\\Role', ':ADMIN/api_rest_role/');
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Api\\Model\\Rest\\Role', ':ADMIN/api_rest_role/');
    }
}
