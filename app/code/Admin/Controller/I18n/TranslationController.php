<?php

namespace Redseanet\Admin\Controller\I18n;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\I18n\Model\Translation as Model;

class TranslationController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_i18n_translation_list');
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_i18n_translation_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Translation');
        } else {
            $root->getChild('head')->setTitle('Add New Translation');
        }
        return $root;
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\I18n\\Model\\Translation', ':ADMIN/i18n_translation/', ['locale', 'string']);
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\I18n\\Model\\Translation', ':ADMIN/i18n_translation/');
    }
}
