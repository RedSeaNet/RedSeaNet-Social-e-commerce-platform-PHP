<?php

namespace Redseanet\Admin\Controller\Api\Rpc;

use Redseanet\Api\Model\Rpc\Role as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class RoleController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_rpc_role_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_rpc_role_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit RPC Role / RPC Role Management');
        } else {
            $root->getChild('head')->setTitle('Add New RPC Role / RPC Role Management');
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Api\\Model\\Rpc\\Role', ':ADMIN/api_rpc_role/list/');
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $segment = new Segment('admin');
            $userArray = $segment->get('user');
            $user = new User();
            $user->load($userArray['id']);
            $result = $this->validateForm($data, ['name']);
            if (!$user->valid($user['username'], $data['crpassword'])) {
                $result['message'][] = ['message' => $this->translate('The current password is incurrect.'), 'level' => 'danger'];
                $result['error'] = 1;
            } else {
                $model = new Model($data);
                if (empty($data['id'])) {
                    $model->setId(null);
                }
                try {
                    $model->save();
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result, ':ADMIN/api_rpc_role/');
    }
}
