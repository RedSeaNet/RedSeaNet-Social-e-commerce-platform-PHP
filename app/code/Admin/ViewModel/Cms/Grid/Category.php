<?php

namespace Redseanet\Admin\ViewModel\Cms\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Cms\Model\Collection\Category as Collection;

class Category extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Cms\\Category::edit',
        'getDeleteAction' => 'Admin\\Cms\\Category::delete'
    ];
    protected $translateDomain = 'cms';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/cms_category/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/cms_category/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
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
            'parent_id' => [
                'label' => 'Parent ID',
            ],
            'name' => [
                'label' => 'Name',
                'class' => 'text-left',
                'use4sort' => false,
                'use4filter' => false
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
                'sortby' => 'cms_category:status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled',
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
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }
}
