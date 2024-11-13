<?php

namespace Redseanet\Admin\ViewModel\Sales\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Sales\Source\Order\Phase;

class Status extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('sales_status/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('sales_status/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit  Status' : 'Add New Status';
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
            'phase_id' => [
                'type' => 'select',
                'label' => 'Phase',
                'required' => 'required',
                'options' => (new Phase())->getSourceArray()
            ],
            'name' => [
                'type' => 'text',
                'required' => 'required',
                'label' => 'Name'
            ],
            'is_default' => [
                'type' => 'select',
                'label' => 'Is Default',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ]
        ];
        $model = $this->getVariable('model');
        if ($model && $model['is_default']) {
            $columns['phase_id']['attrs'] = ['disabled' => 'disabled'];
        }
        return parent::prepareElements($columns);
    }
}
