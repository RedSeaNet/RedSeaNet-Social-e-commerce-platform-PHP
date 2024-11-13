<?php

namespace Redseanet\Admin\ViewModel\Customer\Edit\Attribute;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Eav\Attribute\Input;

class Address extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('customer_attribute_address/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('customer_attribute_address/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Address Attribute' : 'Add New Address Attribute';
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
            'code' => [
                'type' => 'text',
                'label' => 'Code',
                'required' => 'required'
            ],
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    'varchar' => 'Charector',
                    'int' => 'Integer',
                    'decimal' => 'Decimal',
                    'text' => 'Text',
                    'datetime' => 'Date/Time'
                ]
            ],
            'input' => [
                'label' => 'Input Widget',
                'type' => 'select',
                'required' => 'required',
                'options' => array_merge_recursive((new Input())->getSourceArray(), ['Select' => ['address' => 'Address']])
            ],
            'validation' => [
                'label' => 'Validation',
                'type' => 'text'
            ],
            'is_required' => [
                'label' => 'Is Required',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'default_value' => [
                'label' => 'Default Value',
                'type' => 'text'
            ],
            'is_unique' => [
                'label' => 'Is Unique',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'searchable' => [
                'type' => 'hidden',
                'value' => 0
            ],
            'sortable' => [
                'type' => 'hidden',
                'value' => 0
            ],
            'filterable' => [
                'type' => 'hidden',
                'value' => 0
            ],
            'comparable' => [
                'type' => 'hidden',
                'value' => 0
            ]
        ];
        return parent::prepareElements($columns);
    }
}
