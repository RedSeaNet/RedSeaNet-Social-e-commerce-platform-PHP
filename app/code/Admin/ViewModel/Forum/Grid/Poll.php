<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Forum\Model\Collection\Poll as Collection;
use Redseanet\Forum\Model\Collection\Post as PostCollection;

class Poll extends Grid
{
    use \Redseanet\Lib\Traits\Url;

    protected $action = [
        'getVoterAction' => 'Admin\\Forum\\Poll::voter',
        'getEditAction' => 'Admin\\Forum\\Poll::edit',
        'getDeleteAction' => 'Admin\\Forum\\Poll::delete'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Forum\\Poll::delete'
    ];
    protected $translateDomain = 'forum';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_poll/edit/?id=') . $item['id'] . '"title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Edit') . '</span></a>';
    }

    public function getVoterAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_poll/voter/?poll_id=') . $item['id'] . '"title="' . $this->translate('Voter') .
                '"><span class="fa fa-id-badge" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Voter') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_poll/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_poll/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID',
                'handler' => function ($id, &$item) {
                    $postC = new PostCollection();
                    $postC->where(['poll_id' => $id]);
                    $postC->load(true, true);
                    $postIds = [];
                    for ($p = 0; $p < count($postC); $p++) {
                        $postIds[] = '<a href="' . $this->getAdminUrl('forum_post/edit/?id=' . $postC[$p]['id']) . '" >' . $postC[$p]['id'] . '</a>';
                    }
                    $item['post'] = implode(',', $postIds);
                    return $id;
                }
            ],
            'title' => [
                'type' => 'text',
                'label' => 'Title'
            ],
            'post' => [
                'type' => 'text',
                'label' => 'Post',
                'use4filter' => false,
                'use4sort' => false
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
            ],
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
