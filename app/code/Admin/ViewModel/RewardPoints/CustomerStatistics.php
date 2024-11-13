<?php

namespace Redseanet\Admin\ViewModel\RewardPoints;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Customer\Model\Collection\Customer as CustomerCollection;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class CustomerStatistics extends Grid
{
    protected $action = [
        'getCustomerAction' => 'Admin\\Customer\\Manage::edit'
    ];

    public function getCustomerAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_manage/edit/?id=' . $item['id']) .
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
            'total_rewardpoints' => [
                'label' => 'Account Reward Points',
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
        $total_balance->from('reward_points');
        $total_balance->columns(['amount' => new Expression('sum(count)')])
                ->where('`reward_points`.`customer_id`=`main_table`.`id` and `reward_points`.`status`=1');

        $total_consumption = new Select();
        $total_consumption->from('reward_points');
        $total_consumption->columns(['amount' => new Expression('sum(count)')])
                ->where('`reward_points`.`customer_id`=`main_table`.`id` and `reward_points`.`status`=1 and `reward_points`.`comment`!="System Adjustment"')
        ->where->lessThanOrEqualTo('reward_points.count', 0);

        $total_systemadjustment = new Select();
        $total_systemadjustment->from('reward_points');
        $total_systemadjustment->columns(['amount' => new Expression('sum(count)')])
                ->where('`reward_points`.`customer_id`=`main_table`.`id` and `reward_points`.`status`=1 and `reward_points`.`comment`="System Adjustment"');

        $balanceCollection->columns(['*',
            'total_rewardpoints' => $total_balance,
            'total_consumption' => $total_consumption,
            'total_systemadjustment' => $total_systemadjustment
        ])->where('`main_table`.`id`!=0');
        //echo $balanceCollection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        return parent::prepareCollection($balanceCollection);
    }
}
