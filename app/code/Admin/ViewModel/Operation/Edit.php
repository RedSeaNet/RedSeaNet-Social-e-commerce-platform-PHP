<?php

namespace Redseanet\Admin\ViewModel\Operation;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class Edit extends PEdit
{
    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Operation' : 'Add New Operation';
    }

    public function getSaveUrl()
    {
        return $this->getAdminUrl('operation/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('operation/delete/');
        }
        return false;
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'id' => [
                'type' => 'hidden'
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'is_system' => [
                'type' => 'hidden',
                'value' => 0
            ],
            'name' => [
                'type' => 'text',
                'label' => 'Name',
                'required' => 'required',
                'attrs' => [
                    'spellcheck' => 'false'
                ]
            ],
            'description' => [
                'type' => 'text',
                'label' => 'Description'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
