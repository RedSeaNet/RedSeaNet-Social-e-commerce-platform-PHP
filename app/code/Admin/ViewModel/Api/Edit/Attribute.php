<?php

namespace Redseanet\Admin\ViewModel\Api\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Api\Model\Rest\Role as Model;

class Attribute extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('api_rest_attribute/save/');
    }

    public function getTitle()
    {
        return 'Edit Attribute Rules';
    }

    protected function prepareElements($columns = [])
    {
        $model = new Model();
        $model->load($this->getQuery('id'));
        $columns = [
            'csrf' => [
                'type' => 'csrf'
            ],
            'role_id' => [
                'type' => 'hidden',
                'value' => $this->getQuery('id')
            ],
            'role' => [
                'type' => 'label',
                'label' => 'Role',
                'value' => $model['name']
            ],
            'attribute_id[]' => [
                'type' => 'widget',
                'label' => 'Attribute Rules',
                'required' => 'required',
                'widget' => 'attribute'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
