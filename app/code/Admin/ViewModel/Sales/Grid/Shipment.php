<?php

namespace Redseanet\Admin\ViewModel\Sales\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Collection\Shipment as Collection;
use Redseanet\Sales\Source\Shipment\Status;
use Redseanet\Admin\Model\User;

class Shipment extends Grid
{
    protected $action = [
        'getViewAction' => 'Admin\\Sales\\Shipment::view',
        'getPrintAction' => 'Admin\\Sales\\Shipment::print'
    ];
    protected $translateDomain = 'sales';

    public function getViewAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_shipment/view/?id=') . $item['id'] . '" title="' . $this->translate('View') .
                '"><span class="fa fa-fw fa-search" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('View') . '</span></a>';
    }

    public function getPrintAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_shipment/print/?id=') . $item['id'] . '" title="' . $this->translate('Print') .
                '"><span class="fa fa-fw fa-print" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Print') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'increment_id' => [
                'label' => 'ID'
            ],
            'order_increment_id' => [
                'label' => 'Order ID',
                'sortby' => 'sales_order:increment_id'
            ],
            'shipping_method' => [
                'label' => 'Shipping Method'
            ],
            'billing_address' => [
                'label' => 'Billing Address'
            ],
            'shipping_address' => [
                'label' => 'Shipping Address'
            ],
            'warehouse_id' => [
                'label' => 'Warehouse'
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    1 => 'Complete',
                    0 => 'Processing'
                ]
            ],
            'updated_at' => [
                'label' => 'Last Modified'
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->join('sales_order', 'sales_order.id=sales_order_shipment.order_id', ['order_increment_id' => 'increment_id']);
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'sales_order_shipment.created_at';
        }
        return parent::prepareCollection($collection);
    }
}
