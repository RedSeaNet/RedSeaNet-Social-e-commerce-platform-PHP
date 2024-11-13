<?php

namespace Redseanet\Admin\ViewModel\Cms\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Cms\Model\Collection\Block as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Block extends PGrid
{
    protected $translateDomain = 'cms';
    protected $action = [
        'getEditAction' => 'Admin\\Cms\\Block::edit',
        'getDeleteAction' => 'Admin\\Cms\\Block::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/cms_block/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/cms_block/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            'code' => [
                'label' => 'Identifier',
                'class' => 'text-left'
            ],
            'language' => [
                'label' => 'Language',
                'use4sort' => false,
                'use4filter' => false
            ],
            'status' => [
                'label' => 'Status',
                'sortby' => 'cms_block:status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
            ],
            'updated_at' => [
                'type' => 'daterange',
                'label' => 'Updated at',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
            'created_at' => [
                'type' => 'daterange',
                'label' => 'Created at',
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
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }
}
