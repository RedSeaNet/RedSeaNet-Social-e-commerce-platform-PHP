<?php

namespace Redseanet\Admin\Controller\I18n;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Collection\Eav\Type;
use Redseanet\Lib\Model\Language;
use Redseanet\I18n\Source\Locale;

class LanguageController extends AuthActionController
{
    public function listAction()
    {
        return $this->getLayout('admin_i18n_language_list');
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_i18n_language_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Language();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Language');
        } else {
            $root->getChild('head')->setTitle('Add New Language');
        }
        return $root;
    }

    public function saveAction()
    {
        $response = $this->doSave('\\Redseanet\\Lib\\Model\\Language', ':ADMIN/i18n_language/list/', ['code', 'merchant_id'], function ($model, $data) {
            if (!isset($data['name']) || $data['name'] === '') {
                $code = (new Locale())->getSourceArray($data['code']);
                $model->setData('name', $code ?: '');
            }
        });
        if (!$this->getRequest()->getPost('id') && $this->responseData['error'] === 0) {
            $tableGateway = $this->getTableGateway('eav_attribute_label');
            $select = $tableGateway->getSql()->select();
            $select->order('language_id ASC');
            $result = $tableGateway->selectWith($select)->toArray();
            $id = $result[0]['language_id'];
            foreach ($result as $item) {
                if ($item['language_id'] == $id) {
                    $tableGateway->insert([
                        'attribute_id' => $item['attribute_id'],
                        'language_id' => $this->responseData['data']['id'],
                        'label' => $item['label']
                    ]);
                } else {
                    break;
                }
            }
            $prefixes = new Type();
            $prefixes->columns(['code', 'value_table_prefix']);
            foreach ($prefixes as $prefix) {
                foreach (['int', 'decimal', 'varchar', 'text', 'datetime'] as $type) {
                    $tableGateway = $this->getTableGateway($prefix['value_table_prefix'] . '_' . $type);
                    $result = $tableGateway->select();
                    foreach ($result as $item) {
                        if ($item['language_id'] == $id) {
                            $tableGateway->insert([
                                'attribute_id' => $item['attribute_id'],
                                'language_id' => $this->responseData['data']['id'],
                                'entity_id' => $item['entity_id'],
                                'value' => $item['value']
                            ]);
                        } else {
                            break;
                        }
                    }
                }
            }
        }
        return $response;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Lib\\Model\\Language', ':ADMIN/i18n_language/list/');
    }
}
