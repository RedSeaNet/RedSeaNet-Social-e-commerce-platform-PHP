<?php

namespace Redseanet\Lib\ViewModel\Eav;

use Redseanet\Admin\ViewModel\Edit as PEdit;
use Redseanet\Lib\Source\Eav\Attribute\Set;

class BeforeEdit extends PEdit
{
    public function getSaveUrl()
    {
        return $this->getUri()->withQuery('');
    }

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
