<?php

namespace Redseanet\Admin\ViewModel\Api\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Api\Source\RestValidation;

class RestRole extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('api_rest_role/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('api_rest_role/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Role' : 'Add New Role';
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'name' => [
                'type' => 'text',
                'label' => 'Name',
                'required' => 'required'
            ],
            'validation' => [
                'type' => 'select',
                'label' => 'Validation',
                'required' => 'required',
                'options' => (new RestValidation())->getSourceArray()
            ]
        ];
        return parent::prepareElements($columns);
    }
}
