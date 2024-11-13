<?php

namespace Redseanet\Admin\ViewModel\Cms\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Cms\Model\Collection\Page as Collection;
use Redseanet\Cms\Source\Category;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Page extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Cms\\Page::edit',
        'getDeleteAction' => 'Admin\\Cms\\Page::delete'
    ];
    protected $translateDomain = 'cms';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/cms_page/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/cms_page/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            'category_id' => [
                'type' => 'selecttree',
                'label' => 'Category',
                'use4sort' => false,
                'options' => (new Category())->getSourceArrayTree()
            ],
            'title' => [
                'label' => 'Title',
                'class' => 'text-left'
            ],
            'uri_key' => [
                'label' => 'Uri Key',
                'class' => 'text-left',
                'handler' => function ($value) {
                    return rawurldecode($value);
                }
            ],
            'language' => [
                'label' => 'Language',
                'use4sort' => false,
                'use4filter' => false
            ],
            'status' => [
                'label' => 'Status',
                'sortby' => 'cms_page:status',
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
        $collection->join('cms_category_page', 'cms_category_page.page_id=cms_page.id', ['category_id'], 'left');
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }
}
