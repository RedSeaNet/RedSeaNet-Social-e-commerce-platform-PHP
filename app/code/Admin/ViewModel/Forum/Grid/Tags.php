<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Forum\Model\Collection\Tags as Collection;

class Tags extends Grid
{
    protected $action = [
        'getEditAction' => 'Admin\\Forum\\Tags::edit',
        'getDeleteAction' => 'Admin\\Forum\\Tags::delete'
    ];

    public function __clone()
    {
        $this->variables = [];
        $this->children = [];
    }

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_tags/edit/?id=') . $item['id'] .
                '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_tags/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $columns = [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID'
            ],
            'name' => [
                'label' => 'Name'
            ],
            'sys_recommended' => [
                'label' => 'System recommended',
                'type' => 'select',
                'options' => [
                    1 => 'Yes',
                    0 => 'No'
                ]
            ],
            'sort_order' => [
                'label' => 'Sort Order',
                'use4sort' => true,
                'use4filter' => false
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ]
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        if (is_null($collection)) {
            $collection = new Collection();
            $collection->withName();
            if (!$this->getQuery('desc')) {
                $this->query['desc'] = 'created_at';
            }
        }
        return parent::prepareCollection($collection);
    }
}
