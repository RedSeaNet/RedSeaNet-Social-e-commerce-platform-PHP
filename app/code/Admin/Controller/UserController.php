<?php

namespace Redseanet\Admin\Controller;

use Redseanet\Admin\Model\Collection\User as Collection;
use Redseanet\Admin\Model\User as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Expression;

class UserController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_user');
        $segment = new Segment('admin');
        $userArray = $segment->get('user');
        $user = new Model();
        $user->load($userArray['id']);
        $root->getChild('edit', true)->setVariable('model', $user);
        return $root;
    }

    public function logoutAction()
    {
        $segment = new Segment('admin');
        $segment->set('hasLoggedIn', false);
        $segment->offsetUnset('user');
        $url = $this->getRequest()->getQuery('success_url', false);
        return $url === false ? $this->redirect(':ADMIN') : $this->redirect(urldecode($url));
    }

    public function listAction()
    {
        return $this->getLayout('admin_user_list');
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_user_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit User / User Management');
        } else {
            $root->getChild('head')->setTitle('Add New User / User Management');
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Admin\\Model\\User', ':ADMIN/user/list/');
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $segment = new Segment('admin');
            $userArray = $segment->get('user');
            $user = new Model();
            $user->load($userArray['id']);
            $result = $this->validateForm($data, ['username']);
            if (empty($data['cpassword']) && empty($data['password'])) {
                unset($data['password']);
            } elseif (empty($data['cpassword']) || empty($data['password']) || $data['cpassword'] !== $data['password']) {
                $result['message'][] = ['message' => $this->translate('The confirm password is not equal to the password.'), 'level' => 'danger'];
                $result['error'] = 1;
            } elseif (!$user->valid($user['username'], $data['crpassword'])) {
                $result['message'][] = ['message' => $this->translate('The current password is incurrect.'), 'level' => 'danger'];
                $result['error'] = 1;
            }
            if ($result['error'] === 0) {
                $model = new Model($data);
                if (empty($data['id'])) {
                    $model->setId(null);
                }
                try {
                    $this->beginTransaction();
                    $model->save();
                    if (isset($data['id']) && $data['id'] == $user->getId()) {
                        $user->setData($data);
                        $segment->set('user', $user->toArray());
                    }
                    $collection = new Collection();
                    $collection->columns(['count' => new Expression('count(1)')])
                            ->where(['role_id' => 1]);
                    if (count($collection)) {
                        $this->commit();
                        $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                    } else {
                        $this->rollback();
                        $result['message'][] = ['message' => $this->translate('There must be one administrator at least.'), 'level' => 'danger'];
                        $result['error'] = 1;
                    }
                } catch (Exception $e) {
                    $this->rollback();
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        $referer = $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'];
        return $this->response($result, strpos($referer, 'edit') ? ':ADMIN/user/list/' : ':ADMIN/user/');
    }
}
