<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Customer;
use Redseanet\Forum\Model\Collection\Post\Like as Collection;
use Redseanet\Forum\Model\Post;

class PostLike extends Grid
{
    protected $translateDomain = 'forum';
    protected $action = [
        'getDeleteAction' => 'Admin\\Forum\\Post::deleteLike'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Forum\\Post::deleteLike'
    ];

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/deleteLike/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_post/deleteLike/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
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
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('forum_post/likelist/?customer_id=') . $id . '">' . $model->offsetGet('username') . '</a>' : $model->offsetGet('username');
                }
            ],
            'post_id' => [
                'label' => 'Post',
                'handler' => function ($id) {
                    $model = new Post();
                    $model->load($id);
                    return '<a href="' . $this->getAdminUrl('forum_post/likelist/?post_id=') . $id . '">' . $model->offsetGet('title') . '</a>';
                }
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
