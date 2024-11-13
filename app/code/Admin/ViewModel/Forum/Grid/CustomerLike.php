<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Customer;
use Redseanet\Forum\Model\Collection\CustomerLike as Collection;

class CustomerLike extends Grid
{
    protected $action = [
        'getDeleteAction' => 'Admin\\Forum\\CustomerLike::delete'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Forum\\CustomerLike::delete'
    ];
    protected $translateDomain = 'forum';

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_customerlike/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
            '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
            '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
            $this->translate('Delete') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_customerlike/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
            '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID'
            ],
            'customer_id' => [
                'label' => 'Customer',
                'handler' => function ($id) {
                    $model = new Customer();
                    $model->load($id);
                    return '<a href="' . $this->getAdminUrl('forum_customerlike/?customer_id=') . $id . '">' . $model->offsetGet('username') . '</a>';
                }
            ],
            'like_customer_id' => [
                'label' => 'Subscribed Customer',
                'handler' => function ($id) {
                    $model = new Customer();
                    $model->load($id);
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=') . $id . '">' . $model->offsetGet('username') . '</a>' : $model->offsetGet('username');
                }
            ],
            'created_at' => [
                'type' => 'daterange',
                'label' => 'Created at',
                'use4filter' => false,
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
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
