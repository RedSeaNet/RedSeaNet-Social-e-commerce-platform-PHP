<?php

namespace Redseanet\Admin\ViewModel\Cms\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Cms\Source\Category as CategorySource;
use Redseanet\Lib\Source\Language;

class Category extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('cms_category/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('cms_category/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Category' : 'Add New Category';
    }

    protected function prepareElements($columns = [])
    {
        $languages = (new Language())->getSourceArray();
        $model = $this->getVariable('model');
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'parent_id' => [
                'type' => 'select',
                'label' => 'Parent ID',
                'empty_string' => '(NULL)',
                'options' => (new CategorySource())->getSourceArray($model ? $model->getId() : [])
            ],
            'language_id[]' => [
                'type' => 'select',
                'label' => 'Language',
                'required' => 'required',
                'options' => $languages,
                'attrs' => [
                    'multiple' => 'multiple'
                ]
            ],
            'uri_key' => [
                'type' => 'text',
                'label' => 'Uri Key',
                'value' => empty($model['uri_key']) ? '' : rawurldecode($model['uri_key'])
            ],
            'show_navigation' => [
                'label' => 'Show Navigation',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
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
            'name' => [
                'type' => 'multitext',
                'label' => 'Name',
                'required' => 'required',
                'base' => '#language_id',
                'options' => $languages
            ]
        ];
        return parent::prepareElements($columns);
    }
}
