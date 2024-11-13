<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Attribute;

use Redseanet\Admin\ViewModel\Edit;

class Set extends Edit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('catalog_attribute_set/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('catalog_attribute_set/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Product Attribute Set' : 'Add New Product Attribute Set';
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
            'apply' => [
                'type' => 'widget',
                'label' => 'Attributes',
                'widget' => 'apply'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
