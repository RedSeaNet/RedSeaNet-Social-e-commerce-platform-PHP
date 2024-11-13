<?php

namespace Redseanet\Admin\Controller\I18n;

use Redseanet\I18n\Model\City as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\I18n\Model\Country;
use Redseanet\I18n\Model\Region;

class CityController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\I18n\Traits\Currency;

    public function indexAction()
    {
        $root = $this->getLayout('admin_i18n_city_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_i18n_city_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $model['region'] = $model['parent_id'];
            if (is_numeric($model['parent_id'])) {
                $region = new Region();
                $region->load($model['parent_id']);
                if (!empty($region['parent_id'])) {
                    $model['country'] = $region['parent_id'];
                } else {
                    $model['country'] = '';
                }
            } else {
                $model['country'] = '';
            }
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit City');
        }
        return $root;
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $required = [];
            $result = $this->validateForm($data, $required);
            if ($result['error'] === 0) {
                $data['parent_id'] = $data['region'];
                $model = new Model($data);

                if (!isset($data[$model->getPrimaryKey()]) || (int) $data[$model->getPrimaryKey()] === 0) {
                    $model->setId(null);
                }
                try {
                    $model->save();
                    $result['data'] = $model->getArrayCopy();
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    if ($transaction) {
                        $this->rollback();
                    }
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        $redirect = ':ADMIN/i18n_city/';
        return $this->response($result ?? ['error' => 0, 'message' => []], is_null($redirect) ? $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] : $redirect);
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\I18n\\Model\\City', ':ADMIN/i18n_city/');
    }
}
