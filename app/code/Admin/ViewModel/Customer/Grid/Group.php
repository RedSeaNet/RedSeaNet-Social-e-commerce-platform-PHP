<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Customer\Model\Collection\Group as Collection;

class Group extends PGrid
{
    protected $action = [
        'addCustomersAction' => 'Admin\\Customer\\Group::addCustomer',
        'getCustomersAction' => 'Admin\\Customer\\Group::customerList',
        'getEditAction' => 'Admin\\Customer\\Group::edit',
        'getDeleteAction' => 'Admin\\Customer\\Group::delete'
    ];
    protected $translateDomain = 'customer';

    public function addCustomersAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_group/addcustomer/?group_name=' . $item['id']) . '" title="' . $this->translate('Customer') .
                '"><span class="fa fa-fw fa-user-plus" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Customer') . '</span></a>';
    }

    public function getCustomersAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_group/customerlist/?group_name=') . $item['id'] . '" title="' . $this->translate('Customer') .
                '"><span class="fa fa-fw fa-user" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Customer') . '</span></a>';
    }

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_group/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_group/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'name' => [
                'label' => 'Name',
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }
}
