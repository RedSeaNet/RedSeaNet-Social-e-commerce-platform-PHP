<?php

namespace Redseanet\Admin\Controller\Api\Oauth;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Oauth\Model\Consumer as Model;

class ConsumerController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_oauth_consumer_list');
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_oauth_consumer_edit');
        $model = new Model();
        if ($id = $this->getRequest()->getQuery('id')) {
            $model->load($id);
            $root->getChild('head')->setTitle('Edit Consumer / REST - OAuth');
        } else {
            $root->getChild('head')->setTitle('Add New Consumer / REST - OAuth');
            $root->getChild('messages', true)->addMessage($this->translate('Please make sure you have known the usage and risks of APIs.'), 'warning');
        }
        $root->getChild('edit', true)->setVariable('model', $model);
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Oauth\\Model\\Consumer', ':ADMIN/api_oauth_consumer/');
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Oauth\\Model\\Consumer', ':ADMIN/api_oauth_consumer/', ['name', 'key', 'secret', 'callback_url']);
    }
}
