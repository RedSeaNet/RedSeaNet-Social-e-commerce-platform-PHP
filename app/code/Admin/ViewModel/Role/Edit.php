<?php

namespace Redseanet\Admin\ViewModel\Role;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class Edit extends PEdit
{
    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Role' : 'Add New Role';
    }

    public function getSaveUrl()
    {
        return $this->getAdminUrl('role/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('role/delete/');
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
            'name' => [
                'type' => 'text',
                'label' => 'Name',
                'required' => 'required',
                'attrs' => [
                    'spellcheck' => 'false'
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
            'crpassword' => [
                'type' => 'password',
                'label' => 'Current Password',
                'value' => '',
                'required' => 'required',
                'attrs' => [
                    'minlength' => 6,
                    'autocomplete' => 'off'
                ]
            ],
        ];
        return parent::prepareElements($columns);
    }
}
