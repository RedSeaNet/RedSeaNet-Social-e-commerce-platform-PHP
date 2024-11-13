<?php

namespace Redseanet\Admin\Controller\I18n;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Store;

class StoreController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_i18n_store_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_i18n_store_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Store();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Store');
        } else {
            $root->getChild('head')->setTitle('Add New Store');
        }
        return $root;
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Lib\\Model\\Store', ':ADMIN/i18n_store/', ['code', 'merchant_id']);
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Lib\\Model\\Store', ':ADMIN/i18n_store/');
    }
}
