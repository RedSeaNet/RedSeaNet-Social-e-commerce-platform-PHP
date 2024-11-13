<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid\Attribute;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Model\Collection\Eav\Attribute\Set as Collection;

class Set extends Grid
{
    protected $action = [
        'getEditAction' => 'Admin\\Customer\\Attribute\\Set::edit',
        'getDeleteAction' => 'Admin\\Customer\\Attribute\\Set::delete'
    ];
    protected $translateDomain = 'eav';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_attribute_set/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_attribute_set/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'name' => [
                'label' => 'Name'
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->join('eav_entity_type', 'eav_entity_type.id=eav_attribute_set.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Customer::ENTITY_TYPE]);
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'eav_attribute_set.created_at';
        }
        return parent::prepareCollection($collection);
    }
}
