<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Notifications\Model\Collection\Notifications as Collection;
use Redseanet\Admin\Model\User;

class Notifications extends Grid
{
    protected $translateDomain = 'message';
    protected $action = [
        'getDeleteAction' => 'Admin\\Notifications::delete'
    ];

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_notifications/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            'title' => [
                'label' => 'Title',
                'class' => 'text-left',
                'use4sort' => true,
                'use4filter' => true
            ],
            'content' => [
                'label' => 'Content',
                'type' => 'text'
            ],
            'customer_id' => [
                'label' => 'Customer ID',
            ],
            'username' => [
                'label' => 'Username',
                'type' => 'text',
                'handler' => function ($id, &$item) {
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=') . $item['customer_id'] . '">' . $id . '</a>' : $id;
                }
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    1 => 'Read',
                    0 => 'Unread'
                ],
            ],
            'is_app' => [
                'label' => 'Is APP',
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ],
                'use4sort' => false,
                'use4filter' => false
            ],
            'is_sms' => [
                'label' => 'Is SMS',
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ],
                'use4sort' => false,
                'use4filter' => false
            ],
            'administrator' => [
                'label' => 'Administrator'
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
            'updated_at' => [
                'label' => 'Updated at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $collection = new Collection();
        $collection->join('customer_1_index', 'customer_1_index.id=core_notifications.customer_id', ['username'], 'left')
                ->join('admin_user', 'admin_user.id=core_notifications.administrator_id', ['administrator' => 'username'], 'left');

        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }
}
