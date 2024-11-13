<?php

namespace Redseanet\Admin\ViewModel\Customer\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class Media extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('customer_media/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('customer_media/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Customer Media' : 'Add New Customer Media';
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
            'label' => [
                'type' => 'text',
                'label' => 'Name',
                'required' => 'required'
            ],
            'link' => [
                'type' => 'text',
                'label' => 'Link',
                'required' => 'required'
            ],
            'icon' => [
                'type' => 'widget',
                'label' => 'Icon',
                'required' => 'required',
                'widget' => 'icon'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
