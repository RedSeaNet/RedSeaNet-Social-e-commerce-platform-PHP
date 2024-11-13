<?php

namespace Redseanet\Admin\ViewModel\Bargain\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Bargain\Model\Collection\BargainCaseHelp as Collection;
use Redseanet\Admin\Model\User;

class BargainCaseHelp extends Grid
{
    protected $translateDomain = 'bargain';
    protected $action = ['getDeleteAction' => ''];

    public function getDeleteAction($item)
    {
        return '';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
                'use4sort' => true,
                'use4filter' => false
            ],
            'bargain_id' => [
                'label' => 'Bargain ID',
                'use4sort' => true,
                'use4filter' => true
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'use4sort' => true,
                'use4filter' => true,
                'handler' => function ($id) {
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=' . $id) . '">' . $id . '</a>' : $id;
                }
            ],
            'username' => [
                'label' => 'Username',
                'use4sort' => true,
                'use4filter' => true
            ],
            'bargain_case_id' => [
                'label' => 'Bargain Case ID',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'price' => [
                'type' => 'text',
                'label' => 'Price',
                'use4filter' => false,
                'use4sort' => true,
            ],
            'type' => [
                'type' => 'text',
                'label' => 'Type',
                'use4filter' => false,
                'use4sort' => false,
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $collection = new Collection();
        $collection->join('customer_1_index', 'customer_1_index.id=bargain_case_help.customer_id', ['username'], 'left');
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'bargain_case_help.created_at';
        }
        return parent::prepareCollection($collection);
    }
}
