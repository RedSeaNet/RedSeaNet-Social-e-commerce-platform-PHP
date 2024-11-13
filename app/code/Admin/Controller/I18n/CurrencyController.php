<?php

namespace Redseanet\Admin\Controller\I18n;

use Exception;
use Redseanet\I18n\Model\Currency as Model;
use Redseanet\Lib\Controller\AuthActionController;

class CurrencyController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\I18n\Traits\Currency;

    public function indexAction()
    {
        $root = $this->getLayout('admin_i18n_currency_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_i18n_currency_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Currency');
        } else {
            return $this->redirectReferer(':ADMIN/i18n_currency/');
        }
        return $root;
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\I18n\\Model\\Currency', ':ADMIN/i18n_currency/', ['id', 'symbol', 'rate', 'format']);
    }

    public function syncAction()
    {
        $code = $this->getRequest()->getQuery('code', null);
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        $collection = is_null($code) ? $config['i18n/currency/enabled[]'] : (array) $code;
        if (is_string($collection)) {
            $collection = explode(',', $collection);
        }
        return $this->response($this->sync($collection, $base), ':ADMIN/i18n_currency/');
    }
}
