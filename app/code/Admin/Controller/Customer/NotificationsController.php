<?php

namespace Redseanet\Admin\Controller\Customer;

use Exception;
use Redseanet\Notifications\Model\Notifications as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;

class NotificationsController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_notifications_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_notifications_edit');
        $model = new Model();
        if ($id = $this->getRequest()->getQuery('id')) {
            $model->load($id);
            $root->getChild('head')->setTitle('Edit Notifications / Customer Management');
            $root->getChild('edit', true)->setVariable('model', $model);
        } else {
            $root->getChild('head')->setTitle('Add New Notifications / Customer Management');
        }

        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Notifications\\Model\\Notifications', ':ADMIN/customer_notifications/');
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $required = ['customers', 'title'];
            $result = $this->validateForm($data, $required);
            if (0 === $result['error']) {
                $userArray = (new Segment('admin'))->get('user');
                $languageId = Bootstrap::getLanguage()->getId();
                if (!empty($data['customers']) && count($data['customers']) > 0) {
                    for ($c = 0; $c < count($data['customers']); $c++) {
                        $insertData = ['title' => $data['title'], 'content' => $data['content'], 'customer_id' => $data['customers'][$c], 'area' => 'customer', 'level' => 'info', 'is_app' => $data['is_app'], 'is_sms' => $data['is_sms'], 'status' => 0, 'type' => 1, 'administrator_id' => $userArray['id'], 'language_id' => $languageId];
                        $model = new Model();
                        $model->setData($insertData);
                        $model->save();
                    }
                }
            }
        }
        return $this->response($result, ':ADMIN/customer_notifications/');
    }
}
