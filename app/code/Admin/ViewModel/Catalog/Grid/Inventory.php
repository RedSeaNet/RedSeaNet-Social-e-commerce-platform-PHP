<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Catalog\Model\Collection\Warehouse\Inventory as Collection;
use Redseanet\Catalog\Source\Warehouse;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Inventory extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Catalog\\Inventory::Edit',
        'getDeleteAction' => 'Admin\\Catalog\\Inventory::delete'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Catalog\\Inventory::delete'
    ];

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_inventory/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_inventory/edit/?id=' . $item['product_id']) . '" data-method="edit" data-params="id=' . $item['product_id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_inventory/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    protected $translateDomain = 'catalog';

    protected function prepareColumns($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $columns = [
            'warehouse_name' => [
                'label' => 'Warehouse',
                'type' => 'select',
                'options' => (new Warehouse())->getSourceArray()
            ],
            'product_id' => [
                'label' => 'Product ID',
                'type' => 'text',
                'handler' => function ($id) {
                    return $this->hasPermission('Admin\Catalog\Product::edit') ? '<a href="' . $this->getAdminUrl('catalog_product/edit/?id=') . $id . '">' . $id . '</a>' : $id;
                }
            ],
            'sku' => [
                'label' => 'Sku',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => true
            ],
            'barcode' => [
                'label' => 'Barcode',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => true
            ],
            'qty' => [
                'label' => 'Qty',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false,
                'editable' => true,
                'class' => 'editable',
                'attrs' => [
                    'data-href' => $this->getAdminUrl(':ADMIN/catalog_inventory/quickSave/'),
                    'data-method' => 'post',
                    'data-params' => 'csrf=' . $this->getCsrfKey() . '&column=qty',
                ],
                'handler' => function ($id, &$item) {
                    return intval($id);
                }
            ],
            'reserve_qty' => [
                'label' => 'Reserved Qty',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false,
                'editable' => true,
                'class' => 'editable',
                'attrs' => [
                    'data-href' => $this->getAdminUrl(':ADMIN/catalog_inventory/quickSave/'),
                    'data-method' => 'post',
                    'data-params' => 'csrf=' . $this->getCsrfKey() . '&column=reserve_qty',
                ],
                'handler' => function ($id, &$item) {
                    return intval($id);
                }
            ],
            'min_qty' => [
                'label' => 'Minimum Qty',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false,
                'editable' => true,
                'class' => 'editable',
                'attrs' => [
                    'data-href' => $this->getAdminUrl(':ADMIN/catalog_inventory/quickSave/'),
                    'data-method' => 'post',
                    'data-params' => 'csrf=' . $this->getCsrfKey() . '&column=min_qty',
                ],
                'handler' => function ($id, &$item) {
                    return intval($id);
                }
            ],
            'max_qty' => [
                'label' => 'Maximum Qty',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false,
                'editable' => true,
                'class' => 'editable',
                'attrs' => [
                    'data-href' => $this->getAdminUrl(':ADMIN/catalog_inventory/quickSave/'),
                    'data-method' => 'post',
                    'data-params' => 'csrf=' . $this->getCsrfKey() . '&column=max_qty',
                ],
                'handler' => function ($id, &$item) {
                    return intval($id);
                }
            ],
            'is_decimal' => [
                'label' => 'Qty Uses Decimals',
                'type' => 'select',
                'use4sort' => false,
                'use4filter' => true,
                'options' => [
                    '0' => 'No',
                    '1' => 'Yes'
                ]
            ],
            'backorders' => [
                'label' => 'Backorders',
                'type' => 'select',
                'use4sort' => false,
                'use4filter' => true,
                'options' => [
                    '0' => 'No Backorders',
                    '1' => 'Allow Qty Below 0'
                ]
            ],
            'increment' => [
                'label' => 'Qty Increments',
                'type' => 'text',
                'use4sort' => true,
                'use4filter' => false
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    '0' => 'Out of Stock',
                    '1' => 'In Stock'
                ]
            ]
        ];
        $columns['updated_at'] = [
            'type' => 'daterange',
            'label' => 'Updated at',
            'attrs' => [
                'data-toggle' => 'datepicker'
            ],
            'use4sort' => false,
            'use4filter' => false
        ];
        $columns['created_at'] = [
            'type' => 'daterange',
            'label' => 'Created at',
            'attrs' => [
                'data-toggle' => 'datepicker'
            ],
            'use4sort' => false,
            'use4filter' => false
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        if (is_null($collection)) {
            $collection = new Collection();
            $collection->join('warehouse', 'warehouse_inventory.warehouse_id=warehouse.id', ['warehouse_name' => 'name']);
            if (!$this->getQuery('desc')) {
                $this->query['desc'] = 'created_at';
            }
        }
        return parent::prepareCollection($collection);
    }
}
