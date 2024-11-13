<?php

namespace Redseanet\Admin\ViewModel\Catalog;

use Redseanet\Admin\ViewModel\Eav\BeforeEdit as PBeforeEdit;
use Redseanet\Catalog\Source\Set;
use Redseanet\Catalog\Source\Type;

class BeforeEdit extends PBeforeEdit
{
    protected function prepareElements($columns = [])
    {
        return [
            'attribute_set' => [
                'type' => 'select',
                'label' => 'Attribute Set',
                'required' => 'required',
                'options' => (new Set())->getSourceArray()
            ],
            'product_type' => [
                'type' => 'select',
                'label' => 'Product Type',
                'required' => 'required',
                'options' => (new Type())->getSourceArray()
            ]
        ];
    }
}
