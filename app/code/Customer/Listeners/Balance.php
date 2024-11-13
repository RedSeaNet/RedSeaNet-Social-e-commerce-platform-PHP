<?php

namespace Redseanet\Customer\Listeners;

use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Balance as Collection;
use Redseanet\Customer\Model\Balance as BalanceModel;
use Redseanet\Lib\Listeners\ListenerInterface;
use Laminas\Db\Sql\Expression;

class Balance implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Lib\Traits\DB;

    public function calc($event)
    {
        $balance = new Collection();
        $balance->columns(['balance' => new Expression('sum(amount)')])
                ->group('customer_id')
                ->where(['customer_id' => $event['customer']->getId(), 'status' => 1]);
        $balance->load(false, true);
        if ($balance->count()) {
            $event['customer']->setData('balance', $balance[0]['balance']);
        }
    }

    public function beforeSaveRecharge($event)
    {
        unset($event['data']['balance']);
    }

    public function afterSaveRecharge($event)
    {
        $data = $event['data'];
        $recharge = new BalanceModel();
        $recharge->load($data['qty']);
        $recharge->setData([
            'customer_id' => $data['customer_id'],
            'product_id' => $data['product_id'],
            'amount' => $data['qty'],
            'status' => 0
        ])->save();
    }

    public function afterSaveBackendCustomer($event)
    {
        $customer = $event['model'];
        if ($amount = (float) $customer->offsetGet('adjust_balance')) {
            $balance = new BalanceModel();
            $balance->setData([
                'customer_id' => $customer->getId(),
                'amount' => $amount,
                'comment' => 'System Adjustment',
                'status' => 1
            ]);
            $balance->save();
        }
    }
}
