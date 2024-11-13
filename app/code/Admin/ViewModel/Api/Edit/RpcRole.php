<?php

namespace Redseanet\Admin\ViewModel\Api\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class RpcRole extends PEdit
{
    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit RPC Role' : 'Add New RPC Role';
    }

    public function getSaveUrl()
    {
        return $this->getAdminUrl('api_rpc_role/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('api_rpc_role/delete/');
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
