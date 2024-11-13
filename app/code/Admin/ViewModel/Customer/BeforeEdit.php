<?php

namespace Redseanet\Admin\ViewModel\Customer;

use Redseanet\Admin\ViewModel\Eav\BeforeEdit as PBeforeEdit;
use Redseanet\Customer\Source\Set;

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
            ]
        ];
    }
}
