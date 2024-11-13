<?php

namespace Redseanet\Retailer\ViewModel\Eav;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Retailer\Model\Retailer as Retailer;
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
                ->where('(filterable=1 OR sortable=1)');
        //$user = (new Segment('admin'))->get('user');
        $user = (new Segment('customer'))->get('customer');
        $retailer = new Retailer();
        $retailer->load($user['id'], 'customer_id');
        if (empty($columns)) {
            $columns = [
                'id' => [
                    'label' => 'ID',
                ],
                'store_id' => ($retailer['store_id'] ? [
                    'type' => 'hidden',
                    'value' => $retailer['store_id'],
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
            $columns[$attribute['code']] = [
                'label' => $attribute['label'],
                'type' => $attribute['input'],
                'class' => $attribute['validation'],
                'view_model' => $attribute['view_model'],
                'use4sort' => $attribute['sortable'],
                'use4filter' => $attribute['filterable']
            ];
            if (in_array($attribute['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
                $columns[$attribute['code']]['options'] = (new AttributeModel($attribute))->getOptions($languageId);
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
        $user = (new Segment('customer'))->get('customer');
        $retailer = new Retailer();
        $retailer->load($user['id'], 'customer_id');
        if ($retailer['store_id']) {
            $collection->where(['store_id' => $retailer['store_id']]);
        }
        return parent::prepareCollection($collection);
    }
}
