<?php

namespace Redseanet\Admin\ViewModel\Sales\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Customer;
use Redseanet\Sales\Model\Collection\Rma as Collection;
use Redseanet\Sales\Source\Refund\Service;
use Redseanet\Sales\Source\Order\Status;

class Refund extends Grid
{
    protected $translateDomain = 'sales';
    protected $action = ['getViewAction' => 'Admin\\Sales\\Refund::view',
        'getOrderAction' => 'Admin\\Sales\\Order::view',
        'getInvoiceAction' => 'Admin\\Sales\\Invoice::index',
        'getShipmentAction' => 'Admin\\Sales\\Shipment::index'
    ];

    public function getViewAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_refund/view/?id=') . $item['id'] . '" title="' . $this->translate('View') .
                '"><span class="fa fa-fw fa-search" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('View') . '</span></a>';
    }

    public function getOrderAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_order/view/?id=') . $item['order_id'] . '" title="' . $this->translate('View Orders') .
        '"><span class="fa fa-fw fa-money" aria-hidden="true"></span><span class="sr-only">' .
        $this->translate('View Orders') . '</span></a>';
    }

    public function getShipmentAction($item)
    {
        return count($item->getOrder()->getShipment()) ? ('<a href="' . $this->getAdminUrl('sales_shipment/?order_id=') . $item->getOrder()->getId() . '" title="' . $this->translate('Shipment') .
                '"><span class="fa fa-fw fa-plane" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Shipment') . '</span></a>') : false;
    }

    public function getInvoiceAction($item)
    {
        return count($item->getOrder()->getInvoice()) ? ('<a href="' . $this->getAdminUrl('sales_invoice/?order_id=') . $item->getOrder()->getId() . '" title="' . $this->translate('Invoice') .
                '"><span class="fa fa-fw fa-rmb" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Invoice') . '</span></a>') : false;
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'order_increment_id' => [
                'label' => 'Order ID',
            ],
            'customer_name' => [
                'label' => 'Customer',
            ],
            'service' => [
                'label' => 'Service',
                'type' => 'select',
                'options' => (new Service())->getSourceArray()
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    -2 => 'Canceled',
                    -1 => 'Refused',
                    0 => 'Applied',
                    1 => 'Approved',
                    2 => 'Delivering',
                    3 => 'Processing',
                    4 => 'Delivering',
                    5 => 'Complete'
                ]
            ],
            'order_status_id' => [
                'label' => 'Order Status',
                'type' => 'select',
                'options' => (new Status())->getSourceArray()
            ],
            'order_created_at' => [
                'type' => 'daterange',
                'label' => 'Ordered Date',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
            'updated_at' => [
                'label' => 'Last Modified',
            ],
            'created_at' => [
                'label' => 'Create At',
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->join('sales_order', 'sales_rma.order_id=sales_order.id', ['order_increment_id' => 'increment_id', 'order_created_at' => 'created_at', 'order_status_id' => 'status_id'], 'left');
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'sales_rma.created_at';
        }
        $collection = parent::prepareCollection($collection);
        foreach ($collection as $key => $refund) {
            $collection[$key]['customer_name'] = (new Customer())->load($refund['customer_id'])['username'];
        }

        return $collection;
    }
}
