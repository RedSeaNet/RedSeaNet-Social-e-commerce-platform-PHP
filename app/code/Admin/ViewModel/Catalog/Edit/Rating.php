<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;

class Rating extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('catalog_product_rating/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('catalog_product_rating/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Rating' : 'Add New Rating';
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'id' => [
                'label' => 'ID',
                'type' => 'hidden'
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    'Product', 'Order'
                ]
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title',
                'required' => 'required'
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ],
                'required' => 'required'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
