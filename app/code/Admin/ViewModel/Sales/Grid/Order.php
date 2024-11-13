<?php

namespace Redseanet\Admin\ViewModel\Sales\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Log\Model\Payment;
use Redseanet\Payment\Source\Method as PaymentMethod;
use Redseanet\Sales\Model\Collection\Order as Collection;
use Redseanet\Sales\Source\Order\Status;
use Redseanet\Admin\Model\User;

class Order extends Grid
{
    protected $action = [
        'getViewAction' => 'Admin\\Sales\\Order::view',
        'getHoldAction' => 'Admin\\Sales\\Order::hold',
        'getUnholdAction' => 'Admin\\Sales\\Order::unhold',
        'getCancelAction' => 'Admin\\Sales\\Order::cancel',
        'getInvoiceAction' => 'Admin\\Sales\\Invoice::index',
        'getShipmentAction' => 'Admin\\Sales\\Shipment::index',
        'getPrintAction' => 'Admin\\Sales\\Order::print'
    ];
    protected $translateDomain = 'sales';

    public function getInvoiceAction($item)
    {
        return count($item->getInvoice()) ? ('<a href="' . $this->getAdminUrl('sales_invoice/?order_id=') . $item['id'] . '" title="' . $this->translate('Invoice') .
                '"><span class="fa fa-fw fa-money" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Invoice') . '</span></a>') : false;
    }

    public function getShipmentAction($item)
    {
        return count($item->getShipment()) ? ('<a href="' . $this->getAdminUrl('sales_shipment/?order_id=') . $item['id'] . '" title="' . $this->translate('Shipment') .
                '"><span class="fa fa-fw fa-plane" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Shipment') . '</span></a>') : false;
    }

    public function getViewAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_order/view/?id=') . $item['id'] . '" title="' . $this->translate('View') .
                '"><span class="fa fa-fw fa-search" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('View') . '</span></a>';
    }

    public function getHoldAction($item)
    {
        return $item->canHold() ? ('<a href="' . $this->getAdminUrl('sales_order/hold/?id=') . $item['id'] . '" title="' . $this->translate('Hold') .
                '"><span class="fa fa-fw fa-pause-circle-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Hold') . '</span></a>') : false;
    }

    public function getUnholdAction($item)
    {
        return $item->canUnhold() ? ('<a href="' . $this->getAdminUrl('sales_order/unhold/?id=') . $item['id'] . '" title="' . $this->translate('Unhold') .
                '"><span class="fa fa-fw fa-play-circle-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Unhold') . '</span></a>') : false;
    }

    public function getCancelAction($item)
    {
        return $item->canCancel() ? ('<a href="' . $this->getAdminUrl('sales_order/cancel/?id=') . $item['id'] . '" title="' . $this->translate('Cancel') .
                '" onclick="if(!confirm(\'' . $this->translate('Are you sure to cancel this order?') .
                '\'))return false;"><span class="fa fa-fw fa-stop-circle-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Cancel') . '</span></a>') : false;
    }

    public function getPrintAction($item)
    {
        return '<a href="' . $this->getAdminUrl('sales_order/print/?id=') . $item['id'] . '" title="' . $this->translate('Print') .
                '"><span class="fa fa-fw fa-print" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Print') . '</span></a>';
    }

    protected function prepareColumns()
    {
        $currency = $this->getContainer()->get('currency');
        return [
            'increment_id' => [
                'label' => 'Order ID'
            ],
            'customer_id' => [
                'label' => 'Customer ID'
            ],
            'store_id' => [
                'label' => 'Store ID'
            ],
            'bulk_id' => [
                'label' => 'Bulk ID'
            ],
            'shipping_address' => [
                'label' => 'Shipping Address',
                'handler' => function ($value) {
                    return nl2br($value);
                }
            ],
            'payment_method' => [
                'label' => 'Payment Method',
                'type' => 'select',
                'options' => (new PaymentMethod())->getSourceArray()
            ],
            'base_total' => [
                'label' => 'Total',
                'type' => 'price',
                'currency' => $currency
            ],
            'base_total_paid' => [
                'label' => 'Total Paid',
                'type' => 'price',
                'currency' => $currency
            ],
            'status_id' => [
                'label' => 'Order Status',
                'type' => 'select',
                'options' => (new Status())->getSourceArray()
            ],
            'shipping_address' => [
                'label' => 'Shipping Address',
                'handler' => function ($value) {
                    return nl2br($value);
                }
            ],
            'payment_method' => [
                'label' => 'Payment Method',
                'type' => 'select',
                'options' => (new PaymentMethod())->getSourceArray()
            ],
            'created_at' => [
                'type' => 'daterange',
                'label' => 'Ordered Date',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
            'id' => [
                'label' => 'Payed at',
                'handler' => function ($id) {
                    $log = new Payment();
                    $log->load($id, 'order_id');
                    return $log['created_at'];
                },
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ],
                'use4sort' => false,
                'use4filter' => false
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [])
                ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', ['phase' => 'code'])
                ->join('bulk_sale_member', 'bulk_sale_member.order_id=sales_order.id', ['bulk_id'], 'left');
        $condition = $this->getQuery();

        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!empty($condition['shipping_address'])) {
            $collection->where('shipping_address LIKE "%' . $condition['shipping_address'] . '%"');
            unset($this->query['shipping_address']);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }

    public function getAttrs($attrs = [])
    {
        $result = '';
        if (!empty($attrs)) {
            foreach ($attrs as $key => $value) {
                $result .= $key . '="' . $value . '" ';
            }
        }
        return $result;
    }
}
