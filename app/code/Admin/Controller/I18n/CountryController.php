<?php

namespace Redseanet\Admin\Controller\I18n;

use Redseanet\I18n\Model\Country as Model;
use Redseanet\Lib\Controller\AuthActionController;

class CountryController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\I18n\Traits\Currency;

    public function indexAction()
    {
        $root = $this->getLayout('admin_i18n_country_list');

        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_i18n_country_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Country');
        }
        return $root;
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\I18n\\Model\\Country', ':ADMIN/i18n_country/', []);
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\I18n\\Model\\Country', ':ADMIN/i18n_country/');
    }
}
