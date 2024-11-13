<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Customer\Model\Collection\Customer as Collection;
use Redseanet\Customer\Source\Group;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Bootstrap;

class CustomerInGroup extends PGrid
{
    protected $action = [
        'getDeleteAction' => 'Admin\\Customer\\Group::delete',
    ];
    protected $translateDomain = 'customer';

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_group/DeleteCustomerInGroup/') . '" data-method="delete" data-params="group_id=' . $item['group_id'] .
                '&customer_id=' . $item['id'] . '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
                'use4filter' => false,
                'use4sort' => false
            ],
            'username' => [
                'label' => 'Username',
                'handler' => function ($id, &$item) {
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=' . $item['id']) . '">' . $id . '</a>' : $id;
                },
            ],
            'group_name' => [
                'type' => 'select',
                'label' => 'Customer Group',
                'options' => (new Group())->getSourceArray(),
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'customer_in_group.customer_id';
        }
        $collection->join('customer_in_group', 'main_table.id=customer_in_group.customer_id', ['group_id'], 'left')
                ->join('customer_group', 'customer_in_group.group_id=customer_group.id', ['group_name' => 'name'], 'left');
        if ($this->getQuery('group_name')) {
            $collection->where(['customer_group.id' => $this->getQuery('group_name')]);
            unset($this->query['group_name']);
        }
        //echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        return parent::prepareCollection($collection);
    }
}
