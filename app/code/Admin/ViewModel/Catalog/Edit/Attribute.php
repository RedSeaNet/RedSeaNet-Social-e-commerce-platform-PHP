<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Eav\Attribute\Input;

class Attribute extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('catalog_attribute/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('catalog_attribute/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Product Attribute' : 'Add New Product Attribute';
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
                'options' => array_merge_recursive((new Input())->getSourceArray(), ['Text' => ['price' => 'Price']])
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
                'label' => 'Searchable',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'sortable' => [
                'label' => 'Sortable',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'filterable' => [
                'label' => 'Filterable',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'comparable' => [
                'label' => 'Comparable',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'multilingual' => [
                'label' => 'Multilingual',
                'type' => 'select',
                'required' => 'required',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ]
        ];
        return parent::prepareElements($columns);
    }
}
