<?php

namespace Redseanet\Admin\Controller\I18n;

use Redseanet\I18n\Model\County as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\I18n\Model\Country;
use Redseanet\I18n\Model\Region;
use Redseanet\I18n\Model\City;

class CountyController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\I18n\Traits\Currency;

    public function indexAction()
    {
        $root = $this->getLayout('admin_i18n_county_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_i18n_county_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $model['city'] = $model['parent_id'];
            if (is_numeric($model['parent_id'])) {
                $city = new City();
                $city->load($model['parent_id']);
                if (!empty($city['parent_id'])) {
                    $model['region'] = $city['parent_id'];

                    if (is_numeric($model['region'])) {
                        $region = new Region();
                        $region->load($city['parent_id']);
                        if (!empty($region['parent_id'])) {
                            $model['country'] = $region['parent_id'];
                        } else {
                            $model['country'] = '';
                        }
                    } else {
                        $model['country'] = '';
                    }
                } else {
                    $model['city'] = '';
                }
            } else {
                $model['city'] = '';
            }
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit County');
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
                $data['parent_id'] = $data['city'];
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
        $redirect = ':ADMIN/i18n_county/';
        return $this->response($result ?? ['error' => 0, 'message' => []], is_null($redirect) ? $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] : $redirect);
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\I18n\\Model\\County', ':ADMIN/i18n_county/');
    }
}
