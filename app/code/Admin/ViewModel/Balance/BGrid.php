<?php

namespace Redseanet\Admin\ViewModel\Balance;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Balance\Source\DrawType;
use Redseanet\Customer\Model\Collection\Balance as Collection;
use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Customer as CustomerCollection;
use Redseanet\Forum\Model\Post;
use Laminas\Db\Sql\Select;
use Redseanet\Payment\Source\Method as PaymentMethod;
use Redseanet\Log\Model\Payment as Payment;

class BGrid extends Grid
{
    protected $action = [
        'getCustomerAction' => 'Admin\\Customer\\Manage::edit'
    ];

    public function getCustomerAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_manage/edit/?id=') . $item['customer_id'] .
                '" title="' . $this->translate('Customer') .
                '"><span class="fa fa-user" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Customer') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID',
                'handler' => function ($id, &$item) {
                    $CustomerC = new CustomerCollection();
                    $CustomerC->load(true, true);
                    if (count($CustomerC) > 0) {
                        $item['like_customer'] = '<a href="' . $this->getAdminUrl(':ADMIN/customer_manage/edit/?id=') . $CustomerC[0]['id'] . '">' . $CustomerC[0]['username'] . '</a>';
                    } else {
                        $item['like_customer'] = '';
                    }
                    return $id;
                }
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'use4filter' => false,
                'use4sort' => false,
                'handler' => function ($id) {
                    return '<a href="' . $this->getAdminUrl('balance/?customer_id=') . $id . '">' . $id . '</a>';
                }
            ],
            'username' => [
                'type' => 'text',
                'label' => 'Customer',
            ],
            'amount' => [
                'type' => 'price',
                'label' => 'Amount',
                'handler' => function ($amount) {
                    return round($amount, 2);
                },
                'use4filter' => false
            ],
            'comment' => [
                'type' => 'select',
                'label' => 'Comment',
                'use4filter' => true,
                'handler' => function ($comment) {
                    return $this->translate($comment);
                },
                'options' => [
                    'Top Up' => 'Top Up',
                    'Subscribe User' => 'Subscribe User',
                    'Subscribe Fee Gain' => 'Subscribe Fee Gain',
                    'Withdraw Balance' => 'Withdraw Balance',
                    'Cancel Withdraw' => 'Cancel Withdraw',
                    'System Adjustment' => 'System Adjustment'
                ]
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    '0' => 'Pending Payment',
                    '1' => 'Payment Success'
                ]
            ],
            'payment_method' => [
                'label' => 'Payment Method',
                'type' => 'select',
                'use4filter' => false,
                'use4sort' => false,
                'options' => (new PaymentMethod())->getSourceArray()
            ],
            'order_id' => [
                'label' => 'Order',
                'use4filter' => false,
                'use4sort' => false,
                'handler' => function ($id, &$item) {
                    $returnString = '';
                    if (!empty($id)) {
                        $logs = new Payment();
                        $logs->load($id, 'order_id');
                        if (isset($logs->trade_id) && $logs->trade_id != '') {
                            $item['trade_id'] = $logs->trade_id;
                        }
                        $returnString = '<a href="' . $this->getAdminUrl('sales_order/view/?id=' . $id) . '" >' . $id . '</a>';
                    } else {
                    }
                    return $returnString;
                }
            ],
            'trade_id' => [
                'label' => 'Payment trade id',
                'use4filter' => false,
                'use4sort' => false
            ],
            'created_at' => [
                'type' => 'daterange',
                'label' => 'Created at',
                'use4filter' => true,
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $balanceCollection = new CustomerCollection();
        $balanceCollection->columns(['username']);
        $balanceCollection->join('customer_balance', 'customer_balance.customer_id=main_table.id', ['id', 'customer_id', 'amount', 'comment', 'status', 'order_id', 'created_at'], 'right');
        $balanceCollection->join('sales_order', 'sales_order.id=customer_balance.order_id', ['payment_method'], 'left');
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        if (!empty($this->query['customer_id'])) {
            $customerId = intval($this->query['customer_id']);
            $balanceCollection->where('`customer_balance`.`customer_id`=' . $customerId);
            unset($this->query['customer_id']);
        }
        return parent::prepareCollection($balanceCollection);
    }
}
