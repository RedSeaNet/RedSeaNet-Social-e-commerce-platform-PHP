<?php

namespace Redseanet\Admin\Controller\Api\Oauth;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Oauth\Model\Token as Model;

class TokenController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_oauth_token_list');
    }

    public function revokeAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Model();
                    $count = 0;
                    foreach ((array) $data['id'] as $id) {
                        $model->setData(['id' => $id, 'status' => 0])->save();
                        $count++;
                    }
                    $result['message'][] = ['message' => $this->translate('%d token(s) have been revoked successfully.', [$count]), 'level' => 'success'];
                    $result['reload'] = 1;
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while revoking. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'api_oauth_token/');
    }

    public function grantAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Model();
                    $count = 0;
                    foreach ((array) $data['id'] as $id) {
                        $model->setData(['id' => $id, 'status' => 1])->save();
                        $count++;
                    }
                    $result['message'][] = ['message' => $this->translate('%d token(s) have been granted successfully.', [$count]), 'level' => 'success'];
                    $result['reload'] = 1;
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while granting. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'api_oauth_token/');
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Oauth\\Model\\Token', ':ADMIN/api_oauth_token/');
    }
}
