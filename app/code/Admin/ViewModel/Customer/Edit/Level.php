<?php

namespace Redseanet\Admin\ViewModel\Customer\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Language;

class Level extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('customer_level/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('customer_level/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Customer Level' : 'Add New Customer Level';
    }

    protected function prepareElements($columns = [])
    {
        $languages = (new Language())->getSourceArray();
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'level' => [
                'type' => 'tel',
                'label' => 'Level',
                'required' => 'required'
            ],
            'amount' => [
                'type' => 'number',
                'label' => 'Amount',
                'required' => 'required'
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
