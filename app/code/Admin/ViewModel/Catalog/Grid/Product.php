<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Eav\Grid as PGrid;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;

class Product extends PGrid
{
    protected $action = [
        'goProductLinkAction' => 'Admin\\Catalog\\Product::productlink',
        'goPostsAction' => 'Admin\\Catalog\\Forum::edit',
        'getEditAction' => 'Admin\\Catalog\\Product::edit',
        'getDeleteAction' => 'Admin\\Catalog\\Product::delete'
    ];
    protected $messAction = [
        'getExportAction' => 'Admin\\Dataflow\\Product::export'
    ];
    protected $translateDomain = 'catalog';

    public function getEditAction($item)
    {
        $page = 1;
        if (!empty($this->query['page'])) {
            $page = $this->query['page'];
        }
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product/edit/?id=' . $item['id'] . '&page=' . $page) . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function goProductLinkAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product/productlink/?id=') . $item['id'] . '" title="' . $this->translate('Product Linked Management') .
                '"><span class="fa fa-list" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Product Linked Management') . '</span></a>';
    }

    public function goPostsAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_forum/edit/?id=') . $item['id'] . '" title="' . $this->translate('Product Relate Post Management') .
                '"><span class="fa fa-newspaper-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Product Relate Post Management') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getExportAction()
    {
        return '<a href="javascript:void(0);" onclick="var id=\'\';$(\'.grid .table [type=checkbox][value]:checked\').each(function(){id+=$(this).val()+\',\';});location.href=\'' .
                $this->getAdminUrl('dataflow_product/export/?id=') . '\'+id.replace(/\,$/,\'\');" title="' . $this->translate('Export') .
                '"><span>' . $this->translate('Export') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $columns = parent::prepareColumns([
            'id' => [
                'label' => 'ID',
            ],
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden',
                'value' => $user->getStore()->getId(),
                'use4sort' => false,
                'use4filter' => false
            ] : [
                'type' => 'select',
                'options' => (new Store())->getSourceArray(),
                'label' => 'Store'
            ]),
            'name' => [
                'label' => 'Name',
                'type' => 'text',
                'filterby' => 'name[like]'
            ],
            'sku' => [
                'label' => 'SKU',
                'type' => 'text'
            ]
        ]);
        $columns['updated_at'] = [
            'type' => 'daterange',
            'label' => 'Updated at',
            'attrs' => [
                'data-toggle' => 'datepicker'
            ]
        ];
        $columns['created_at'] = [
            'type' => 'daterange',
            'label' => 'Created at',
            'attrs' => [
                'data-toggle' => 'datepicker'
            ]
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        if (is_null($collection)) {
            $collection = new Collection();
            $userArray = (new Segment('admin'))->get('user');
            $user = new User();
            $user->load($userArray['id']);
            if ($user->getStore()) {
                $collection->where(['store_id' => $user->getStore()->getId()]);
            }
            if (!$this->getQuery('desc')) {
                $this->query['desc'] = 'created_at';
            }
        }
        return parent::prepareCollection($collection);
    }
}
