<?php

namespace Redseanet\Admin\Controller\Customer;

use Exception;
use Redseanet\Customer\Model\Balance\Draw as Model;
use Redseanet\Lib\Controller\AuthActionController;

class DrawController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_balance_draw_list');
    }

    public function editAction()
    {
        $query = $this->getRequest()->getQuery();
        if (isset($query['id'])) {
            $root = $this->getLayout('admin_balance_draw_edit');
            $model = new Model();
            $model->load($query['id']);
            if ($model->getId()) {
                $root->getChild('edit', true)->setVariable('model', $model);
                return $root;
            }
        }
        return $this->notFoundAction();
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id', 'status']);
            if ($result['error'] === 0) {
                try {
                    $model = new Model();
                    $model->setData([
                        'id' => $data['id'],
                        'status' => $data['status']
                    ])->save();
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], ':ADMIN/customer_draw/');
    }
}
