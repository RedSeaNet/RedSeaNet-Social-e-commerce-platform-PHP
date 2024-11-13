<?php

namespace Redseanet\Customer\Listeners;

use Redseanet\Customer\Model\Collection\Level as Collection;
use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Sales\Model\Collection\Order;
use Laminas\Db\Sql\Expression;

class Level implements ListenerInterface
{
    public function calc($event)
    {
        $orders = new Order();
        $orders->columns(['amount' => new Expression('sum(base_total)')])
                ->join('sales_order_status', 'sales_order.status_id=sales_order_status.id', [], 'left')
                ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                ->where([
                    'customer_id' => $event['customer']->getId(),
                    'sales_order_phase.code' => 'complete'
                ])->group('customer_id');
        if ($orders->count()) {
            $levels = new Collection();
            $levels->order('amount DESC, level DESC')
                    ->limit(1)
                    ->where
                    ->lessThanOrEqualTo('amount', $orders[0]['amount']);
            if ($levels->count()) {
                $event['customer']->setData('level', $levels[0]);
            }
        }
    }

    public function collate()
    {
        $customers = new Customer();
        $customers->where(['status' => 1]);
        $orders = new Order();
        $orders->columns(['customer_id', 'amount' => new Expression('sum(base_total)')])
                ->join('sales_order_status', 'sales_order.status_id=sales_order_status.id', [], 'left')
                ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                ->where(['sales_order_phase.code' => 'complete'])
                ->group('customer_id');
        $amount = [];
        $orders->walk(function ($order) use (&$amount) {
            $amount[$order['customer_id']] = $order['amount'];
        });
        foreach ($customers as $customer) {
            if (isset($amount[$customer->getId()])) {
                $levels = new Collection();
                $levels->order('amount DESC, level DESC')
                        ->limit(1)
                        ->where
                        ->lessThanOrEqualTo('amount', $amount[$customer->getId()]);
                if ($levels->count()) {
                    $customer->setData('level', $levels[0]->getId())->save();
                }
            }
        }
    }
}
