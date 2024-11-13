<?php

namespace Redseanet\Admin\ViewModel\Balance;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Balance\Source\DrawType;
use Redseanet\Customer\Model\Collection\Balance as Collection;
use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Customer as CustomerCollection;
use Redseanet\Forum\Model\Post;
use Redseanet\Payment\Source\Method as PaymentMethod;
use Redseanet\Log\Model\Payment as Payment;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Redseanet\Lib\Bootstrap;

class CustomerStatistics extends Grid
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
                    if (!$item['available_balance'] > 0) {
                        $item['available_balance'] = 0;
                    }
                    if (!$item['total_balance'] > 0) {
                        $item['total_balance'] = 0;
                    }
                    if (!$item['total_gains'] > 0) {
                        $item['total_gains'] = 0;
                    }
                    if (!$item['total_consumption'] > 0) {
                        $item['total_consumption'] = 0;
                    }
                    if (!$item['total_topup'] > 0) {
                        $item['total_topup'] = 0;
                    }
                    if (!$item['total_withdraw'] > 0) {
                        $item['total_withdraw'] = 0;
                    }
                    if (!$item['total_systemadjustment'] > 0) {
                        $item['total_systemadjustment'] = 0;
                    }

                    return $id;
                }
            ],
            'username' => [
                'label' => 'Username',
                'use4filter' => true,
                'use4sort' => true
            ],
            'total_balance' => [
                'label' => 'Account Balance',
                'use4filter' => false,
                'use4sort' => true,
                'handler' => function ($value) {
                    return round($value, 2);
                }
            ],
            'total_consumption' => [
                'label' => 'Total Consumption',
                'use4filter' => false,
                'use4sort' => true,
                'handler' => function ($value) {
                    return round($value, 2);
                }
            ],
            'total_topup' => [
                'label' => 'Total Top Up',
                'use4filter' => false,
                'use4sort' => true,
                'handler' => function ($value) {
                    return round($value, 2);
                }
            ],
            'total_withdraw' => [
                'label' => 'Total Withdraw',
                'use4filter' => false,
                'use4sort' => true,
                'handler' => function ($value) {
                    return round($value, 2);
                }
            ],
            'total_systemadjustment' => [
                'label' => 'System Adjustment',
                'use4filter' => false,
                'use4sort' => true,
                'handler' => function ($value) {
                    return round($value, 2);
                }
            ],
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $balanceCollection = new CustomerCollection();
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        $total_balance = new Select();
        $total_balance->from('customer_balance');
        $total_balance->columns(['amount' => new Expression('sum(amount)')])
                ->where('`customer_balance`.`customer_id`=`main_table`.`id` and `customer_balance`.`status`=1');

        $total_consumption = new Select();
        $total_consumption->from('customer_balance');
        $total_consumption->columns(['amount' => new Expression('sum(amount)')])
                ->where('`customer_balance`.`customer_id`=`main_table`.`id` and `customer_balance`.`status`=1 and `customer_balance`.`comment`!="System Adjustment"')
        ->where->lessThanOrEqualTo('customer_balance.amount', 0);

        $total_topup = new Select();
        $total_topup->from('customer_balance');
        $total_topup->columns(['amount' => new Expression('sum(amount)')])
                ->where('`customer_balance`.`customer_id`=`main_table`.`id` and `customer_balance`.`status`=1 and `customer_balance`.`comment`="Top Up"')
        ->where->greaterThanOrEqualTo('customer_balance.amount', 0);

        $total_withdraw = new Select();
        $total_withdraw->from('customer_balance');
        $total_withdraw->columns(['amount' => new Expression('sum(amount)')])
                ->where('`customer_balance`.`customer_id`=`main_table`.`id` and `customer_balance`.`status`=1 and (`customer_balance`.`comment`="Withdraw Balance" or `customer_balance`.`comment`="Cancel Withdraw")');
        $total_systemadjustment = new Select();
        $total_systemadjustment->from('customer_balance');
        $total_systemadjustment->columns(['amount' => new Expression('sum(amount)')])
                ->where('`customer_balance`.`customer_id`=`main_table`.`id` and `customer_balance`.`status`=1 and `customer_balance`.`comment`="System Adjustment"');

        $balanceCollection->columns(['*',
            'total_balance' => $total_balance,
            'total_consumption' => $total_consumption,
            'total_topup' => $total_topup,
            'total_withdraw' => $total_withdraw,
            'total_systemadjustment' => $total_systemadjustment
        ])->where('`main_table`.`id`!=0');
        //echo $balanceCollection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        return parent::prepareCollection($balanceCollection);
    }
}
