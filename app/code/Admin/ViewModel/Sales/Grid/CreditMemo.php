<?php

namespace Redseanet\Admin\ViewModel\Sales\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Collection\CreditMemo as Collection;
use Redseanet\Admin\Model\User;

class CreditMemo extends Grid
{
    protected $action = [
        'getOrderAction' => 'Admin\\Sales\\Order::view',
        'getViewAction' => 'Admin\\Sales\\Creditmemo::view',
        'getPrintAction' => 'Admin\\Sales\\Creditmemo::print'
    ];
    protected $translateDomain = 'sales';

    public function getViewAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_creditmemo/view/?id=') . $item['id'] . '" title="' . $this->translate('View') .
                '"><span class="fa fa-fw fa-search" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('View') . '</span></a>';
    }

    public function getPrintAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_creditmemo/print/?id=') . $item['id'] . '" title="' . $this->translate('Print') .
                '"><span class="fa fa-fw fa-print" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Print') . '</span></a>';
    }

    public function getOrderAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_order/view/?id=') . $item['order_id'] . '" title="' . $this->translate('View Orders') .
        '"><span class="fa fa-fw fa-money" aria-hidden="true"></span><span class="sr-only">' .
        $this->translate('View Orders') . '</span></a>';
    }

    protected function prepareColumns()
    {
        $currency = $this->getContainer()->get('currency');
        return [
            'increment_id' => [
                'label' => 'ID'
            ],
            'order_increment_id' => [
                'label' => 'Order ID',
                'sortby' => 'sales_order:increment_id'
            ],
            'store_id' => [
                'label' => 'Store ID'
            ],
            'base_total' => [
                'label' => 'Total',
                'currency' => $currency,
                'type' => 'price'
            ],
            'warehouse_id' => [
                'label' => 'Warehouse',
                'currency' => $currency
            ],
            'order_created_at' => [
                'type' => 'daterange',
                'label' => 'Ordered Date',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
            'updated_at' => [
                'label' => 'Last Modified'
            ],
            'created_at' => [
                'label' => 'Created At'
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->join('sales_order', 'sales_order.id=sales_order_creditmemo.order_id', ['order_increment_id' => 'increment_id', 'order_created_at' => 'created_at']);
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'sales_order_creditmemo.created_at';
        }
        return parent::prepareCollection($collection);
    }
}
