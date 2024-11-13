<?php

namespace Redseanet\Admin\ViewModel\Sales\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Sales\Model\Collection\Cart as Collection;

class Cart extends Grid
{
    protected $translateDomain = 'sales';
    protected $action = [
        'getViewAction' => 'Admin\\Sales\\Cart::view',
        'getDeleteAction' => 'Admin\\Sales\\Cart::delete'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Sales\\Sales::delete'
    ];

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/sales_cart/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getViewAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_cart/view/?id=' . $item['id']) . '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('View') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/sales_cart/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    public function getRowLink($item)
    {
        return $this->getAdminUrl('sales_cart/view/?id=' . $item['id']);
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID'
            ],
            'customer_id' => [
                'label' => 'Customer ID'
            ],
            'username' => [
                'label' => 'Username',
                'sortby' => 'customer_1_index:username',
            ],
            'currency' => [
                'label' => 'Currency'
            ],
            'status' => [
                'label' => 'Status',
                'sortby' => 'sales_cart:status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
            ],
            'total' => [
                'label' => 'Total'
            ],
            'created_at' => [
                'label' => 'Created at',
                'use4filter' => false
            ],
            'updated_at' => [
                'label' => 'Last Modified',
                'use4filter' => false
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $expired = date('Y-m-d H:i:s', time() - 3600 * 72);
        $collection->columns(['id', 'customer_id', 'currency', 'status', 'total', 'created_at', 'updated_at'])
                //->where(['status' => 1])
                //->where('((updated_at IS NULL AND created_at <= "' . $expired . '") OR (updated_at <= "' . $expired . '"))')
                ->order('updated_at DESC, created_at DESC');
        //->where->greaterThan('subtotal', 0);
        $collection->join('customer_1_index', 'customer_1_index.id=sales_cart.customer_id', ['username'], 'left');
        return parent::prepareCollection($collection);
    }
}
