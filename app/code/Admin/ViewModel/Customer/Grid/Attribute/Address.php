<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid\Attribute;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Customer\Model\Address as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Eav\Attribute as Collection;

class Address extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Customer\\Attribute\\Address::edit',
        'getDeleteAction' => 'Admin\\Customer\\Attribute\\Address::delete'
    ];
    protected $translateDomain = 'eav';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_attribute_address/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_attribute_address/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->withLabel(Bootstrap::getLanguage()->getId())
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'eav_attribute.created_at';
        }
        return parent::prepareCollection($collection);
    }
}
