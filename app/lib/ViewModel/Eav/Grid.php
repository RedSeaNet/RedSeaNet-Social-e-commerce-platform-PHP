<?php

namespace Redseanet\Lib\ViewModel\Eav;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Eav\Attribute as AttributeModel;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Store;

abstract class Grid extends PGrid
{
    protected function prepareColumns($columns = [])
    {
        $attributes = new Attribute();
        $languageId = Bootstrap::getLanguage()->getId();
        $collection = $this->getVariable('collection');
        $attributes->withLabel($languageId)
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'right')
                ->where(['eav_entity_type.code' => $collection::ENTITY_TYPE])
                ->where('(filterable=1 OR sortable=1)')
                ->order('eav_attribute.id');
        $user = $this->getSegment('admin')->get('user');
        if (empty($columns)) {
            $columns = [
                'id' => [
                    'label' => 'ID',
                ],
                'store_id' => ($user->getStore() ? [
                    'type' => 'hidden',
                    'value' => $user->getStore()->getId(),
                    'use4sort' => false,
                    'use4filter' => false
                ] : [
                    'type' => 'select',
                    'options' => (new Store())->getSourceArray(),
                    'label' => 'Store'
                ])
            ];
        }
        foreach ($attributes as $attribute) {
            if (!isset($columns[$attribute['code']])) {
                $columns[$attribute['code']] = [
                    'label' => $attribute['label'],
                    'type' => in_array($attribute['input'], ['select', 'radio', 'checkbox', 'multiselect']) ? 'select' : $attribute['input'],
                    'class' => $attribute['validation'],
                    'view_model' => $attribute['view_model'],
                    'use4sort' => $attribute['sortable'],
                    'use4filter' => $attribute['filterable']
                ];
            }
            if (in_array($attribute['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
                $columns[$attribute['code']]['options'] = (new AttributeModel($attribute))->getOptions($languageId);
            } elseif ($attribute['input'] === 'bool') {
                $columns[$attribute['code']]['options'] = [1 => 'Yes', 0 => 'No'];
            }
        }
        $columns['status'] = [
            'type' => 'select',
            'label' => 'Status',
            'options' => [
                1 => 'Enabled',
                0 => 'Disabled'
            ]
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        $user = $this->getSegment('admin')->get('user');
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        return parent::prepareCollection($collection);
    }
}
