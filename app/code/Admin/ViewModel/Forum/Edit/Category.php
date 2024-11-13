<?php

namespace Redseanet\Admin\ViewModel\Forum\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Model\Collection\Language;

class Category extends PEdit
{
    protected $hasUploadingFile = true;

    public function getSaveUrl()
    {
        return $this->getAdminUrl('forum_category/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('forum_category/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Category' : 'Add New Category';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden'
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'parent_id' => [
                'type' => 'hidden',
                'value' => $this->getQuery('pid', '')
            ],
            'uri_key' => [
                'type' => 'text',
                'label' => 'Uri Key'
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ],
                'required' => 'required'
            ],
            'sort_order' => [
                'type' => 'tel',
                'label' => 'Sort'
            ]
        ];
        foreach (new Language() as $language) {
            $columns['name[' . $language->getId() . ']'] = [
                'type' => 'text',
                'required' => 'required',
                'label' => $this->translate('Name') . '[' . $language['name'] . ']',
                'value' => $model['name'][$language->getId()] ?? ''
            ];
        }
        return parent::prepareElements($columns);
    }
}
