<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Eav\Attribute as Collection;

class Attribute extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Catalog\\Attribute::edit',
        'getDeleteAction' => 'Admin\\Catalog\\Attribute::delete'
    ];
    protected $translateDomain = 'eav';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_attribute/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_attribute/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'code' => [
                'label' => 'Code',
                'sortby' => 'eav_attribute:code'
            ],
            'label' => [
                'label' => 'Label'
            ],
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'options' => [
                    'varchar' => 'Charector',
                    'int' => 'Integer',
                    'decimal' => 'Decimal',
                    'text' => 'Text',
                    'datetime' => 'Date/Time'
                ]
            ],
            'searchable' => [
                'label' => 'Searchable',
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'filterable' => [
                'label' => 'Filterable',
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'sortable' => [
                'label' => 'Sortable',
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'comparable' => [
                'label' => 'Comparable',
                'type' => 'select',
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
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->withLabel(Bootstrap::getLanguage()->getId())
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Product::ENTITY_TYPE]);
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'eav_attribute.created_at';
        }
        return parent::prepareCollection($collection);
    }
}
