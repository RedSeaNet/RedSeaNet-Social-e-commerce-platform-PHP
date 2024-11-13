<?php

namespace Redseanet\Customer\ViewModel\Dashboard;

use Redseanet\Customer\ViewModel\Account;
use Redseanet\Sales\Model\Collection\Order;
use Laminas\Db\Sql\Expression;

class Profile extends Account
{
    public function getPendingPayment()
    {
        $collection = new Order();
        $collection->columns(['count' => new Expression('count(sales_order.id)')])
                ->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [], 'left')
                ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                ->where([
                    '(sales_order_phase.code=\'pending_payment\' OR sales_order_phase.code=\'pending\')',
                    'customer_id' => $this->getCustomer()->getId()
                ])
                ->group('sales_order_phase.id');
        $collection->load(true, true);
        $count = 0;
        foreach ($collection as $item) {
            $count += $item['count'];
        }
        return $count;
    }

    public function getShipping()
    {
        $collection = new Order();
        $collection->columns(['count' => new Expression('count(sales_order.id)')])
                ->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [], 'left')
                ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                ->where([
                    'sales_order_phase.code' => 'complete',
                    'sales_order_status.is_default' => 1,
                    'customer_id' => $this->getCustomer()->getId()
                ])->group('sales_order_phase.id');
        $collection->load(true, true);
        return count($collection) ? $collection[0]['count'] : 0;
    }

    public function getReviewing()
    {
        $collection = new Order();
        $collection->columns(['count' => new Expression('count(sales_order.id)')])
                ->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [], 'left')
                ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                ->join('review', 'review.order_id=sales_order.id', [], 'left')
                ->where([
                    'sales_order_phase.code' => 'complete',
                    'sales_order.customer_id' => $this->getCustomer()->getId()
                ])->group('sales_order_phase.id')
                ->having(['count(review.id)=0']);
        $collection->load(true, true);
        return count($collection) ? $collection[0]['count'] : 0;
    }
}
