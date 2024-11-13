<?php

namespace Redseanet\Admin\Controller\Customer;

use Exception;
use Redseanet\Customer\Model\Level as Model;
use Redseanet\Lib\Controller\AuthActionController;

class LevelController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_customer_level_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_customer_level_edit');
        $model = new Model();
        if ($id = $this->getRequest()->getQuery('id')) {
            $model->load($id);
            $root->getChild('head')->setTitle('Edit Customer Level / Customer Management');
        } else {
            $root->getChild('head')->setTitle('Add New Customer Level / Customer Management');
        }
        $root->getChild('edit', true)->setVariable('model', $model);
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Customer\\Model\\Level', ':ADMIN/customer_level/');
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Customer\\Model\\Level', ':ADMIN/customer_level/', ['level']);
    }
}
