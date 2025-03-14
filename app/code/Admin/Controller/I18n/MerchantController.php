<?php

namespace Redseanet\Admin\Controller\I18n;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Merchant;

class MerchantController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_i18n_merchant_list');
        return $root;
    }
    public function editAction()
    {
        $root = $this->getLayout('admin_i18n_merchant_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Merchant();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Merchant');
        } else {
            $root->getChild('head')->setTitle('Add New Merchant');
        }
        return $root;
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Lib\\Model\\Merchant', ':ADMIN/i18n_merchant/', ['code']);
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Lib\\Model\\Merchant', ':ADMIN/i18n_merchant/');
    }
}
