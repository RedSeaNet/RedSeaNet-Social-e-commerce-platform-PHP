<?php

namespace Redseanet\Admin\ViewModel\RewardPoints;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Collection\Customer as CustomerCollection;

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
                    return $id;
                }
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'use4filter' => false,
                'use4sort' => false
            ],
            'username' => [
                'label' => 'Username',
                'use4filter' => true,
                'use4sort' => false,
                'type' => 'text',
                'handler' => function ($id, &$item) {
                    $returnString = '';
                    if (!empty($id)) {
                        $returnString = '<a href="' . $this->getAdminUrl('rewardpoints/?username=' . $id) . '" >' . $id . '</a>';
                    } else {
                    }
                    return $returnString;
                }
            ],
            'count' => [
                'type' => 'price',
                'label' => 'Amount',
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
                    '0' => 'Disable',
                    '1' => 'Enable'
                ]
            ],
            'order_id' => [
                'label' => 'Order',
                'use4filter' => false,
                'use4sort' => false,
                'handler' => function ($id, &$item) {
                    $returnString = '';
                    if (!empty($id)) {
                        $returnString = '<a href="' . $this->getAdminUrl('sales_order/view/?id=' . $id) . '" >' . $id . '</a>';
                    } else {
                    }
                    return $returnString;
                }
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
        $balanceCollection->join('reward_points', 'reward_points.customer_id=main_table.id', ['id', 'customer_id', 'count', 'comment', 'status', 'order_id', 'created_at'], 'right');
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'reward_points.created_at';
        }
        if (!empty($this->query['username'])) {
            $username = $this->query['username'];
            $balanceCollection->where("`main_table`.`username`='" . $username . "'");
            unset($this->query['username']);
        }
        return parent::prepareCollection($balanceCollection);
    }
}
