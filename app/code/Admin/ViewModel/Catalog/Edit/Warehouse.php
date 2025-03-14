<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Store;
use Redseanet\Lib\Session\Segment;

class Warehouse extends PEdit
{
    protected $hasUploadingFile = true;

    public function getSaveUrl()
    {
        return $this->getAdminUrl('catalog_warehouse/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('catalog_warehouse/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Warehouse' : 'Add New Warehouse';
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'csrf' => [
                'type' => 'csrf'
            ],
            'id' => [
                'type' => 'hidden'
            ],
            'name' => [
                'label' => 'Name',
                'type' => 'text',
                'required' => 'required'
            ],
            'country' => [
                'label' => 'Country/Region',
                'type' => 'text',
                'required' => 'required',
                'attrs' => ['maxlength' => 2]
            ],
            'region' => [
                'label' => 'Region',
                'type' => 'text',
                'required' => 'required'
            ],
            'city' => [
                'label' => 'City',
                'type' => 'text',
                'required' => 'required'
            ],
            'address' => [
                'label' => 'Address',
                'type' => 'text',
                'required' => 'required'
            ],
            'contact_info' => [
                'label' => 'Contact Info',
                'type' => 'text',
                'required' => 'required'
            ],
            'longitude' => [
                'label' => 'Longitude',
                'type' => 'text'
            ],
            'latitude' => [
                'label' => 'Latitude',
                'type' => 'text'
            ],
            'open_at' => [
                'label' => 'Open At',
                'type' => 'time',
                'attrs' => [
                    'data-toggle' => 'timepicker'
                ]
            ],
            'close_at' => [
                'label' => 'Close At',
                'type' => 'time',
                'attrs' => [
                    'data-toggle' => 'timepicker'
                ]
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    '0' => 'Disable',
                    '1' => 'Enable'
                ],
                'required' => 'required'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
