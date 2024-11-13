<?php

namespace Redseanet\Admin\ViewModel\Customer\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class Group extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('customer_group/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('customer_group/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Customer Group' : 'Add New Customer Group';
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
            ]
        ];
        return parent::prepareElements($columns);
    }
}
