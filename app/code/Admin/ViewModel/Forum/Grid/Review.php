<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Customer;
use Redseanet\Forum\Model\Collection\Post\Review as Collection;

class Review extends Grid
{
    protected $action = [
        'getEditAction' => 'Admin\\Forum\\Review::edit',
        'getCloseAction' => 'Admin\\Forum\\Review::close',
        'getDeleteAction' => 'Admin\\Forum\\Review::delete'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Forum\\Review::delete',
        'getMessCloseAction' => 'Admin\\Forum\\Review::close'
    ];
    protected $translateDomain = 'forum';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_review/edit/?id=') . $item['id'] . '"title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_review/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getCloseAction($item)
    {
        if ($item['status'] >= 0) {
            return '<a href="' . $this->getAdminUrl(':ADMIN/forum_review/close/') .
                    '" data-method="post" data-params="id=' . $item['id'] . '" title="' . $this->translate('Close') .
                    '"><span class="fa fa-fw fa-pause" aria-hidden="true"></span><span class="sr-only">' .
                    $this->translate('Close') . '</span></a>';
        }
        return '';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_review/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    public function getMessCloseAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_review/close/') . '" data-method="post" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Close') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID'
            ],
            'post_id' => [
                'label' => 'Post'
            ],
            'customer_id' => [
                'label' => 'Customer',
                'handler' => function ($id) {
                    $model = new Customer();
                    $model->load($id);
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=') . $id . '">' . $model->offsetGet('username') . '</a>' : $model->offsetGet('username');
                }
            ],
            'subject' => [
                'type' => 'text',
                'label' => 'Subject'
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    3 => 'Impeached',
                    2 => 'Edited',
                    1 => 'Approved',
                    0 => 'New',
                    -1 => 'Closed'
                ]
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
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
