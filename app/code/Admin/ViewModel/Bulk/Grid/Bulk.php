<?php

namespace Redseanet\Admin\ViewModel\Bulk\Grid;

use Redseanet\Admin\ViewModel\Eav\Grid as PGrid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Store;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Admin\Model\User;

class Bulk extends PGrid
{
    protected $translateDomain = 'bulk';
    protected $action = [
        'getEditAction' => 'Admin\\Catalog\\Product::edit'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product/edit/?id=' . $item['id']) . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
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
        $collection = new Collection();
        $collection->where("bulk_price!=''");
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }
}
