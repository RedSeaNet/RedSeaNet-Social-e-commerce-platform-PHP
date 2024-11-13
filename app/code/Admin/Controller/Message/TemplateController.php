<?php

namespace Redseanet\Admin\Controller\Message;

use Exception;
use Redseanet\Message\Model\Template as Model;
use Redseanet\Lib\Controller\AuthActionController;

class TemplateController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_message_template_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_message_template_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Template / Message Template');
        } else {
            $root->getChild('head')->setTitle('Add New Template / Message Template');
        }
        return $root;
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Message\\Model\\Template', ':ADMIN/message_template/', ['code', 'language_id']);
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Message\\Model\\Template', ':ADMIN/message_template/');
    }
}
