<?php

namespace Redseanet\Balance\Listeners;

use Redseanet\Customer\Model\Collection\Balance as Collection;
use Redseanet\Sales\Model\Collection\Order;

class Cron
{
    use \Redseanet\Lib\Traits\Container;

    public function activation()
    {
        if ($days = $this->getContainer()->get('config')['balance/general/activating']) {
            $orders = new Order();
            $orders->columns(['id'])
                    ->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [], 'left')
                    ->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [], 'left')
                    ->where(['sales_order_phase.code' => 'complete'])
            ->where->lessThanOrEqualTo('created_at', date('Y-m-d H:i:s', strtotime('-' . $days . 'days')));
            $collection = new Collection();
            $collection->in('order_id', $orders)
                    ->columns(['id'])
                    ->where(['status' => 0])
            ->where->greaterThan('count', 0);
            foreach ($collection as $record) {
                $record->setData('status', 1)->save();
            }
        }
    }
}
