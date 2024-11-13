<?php

namespace Redseanet\Sales\ViewModel;

use Redseanet\Customer\ViewModel\Account;
use Redseanet\Sales\Model\Collection\Order as Collection;
use Redseanet\Catalog\Model\Collection\Product\Review;

class Order extends Account
{
    use \Redseanet\Lib\Traits\Filter;

    use \Redseanet\Lib\Traits\Text;

    public function getOrders()
    {
        $collection = new Collection();
        $collection->where(['sales_order.customer_id' => $this->getCustomer()->getId()])
                ->order('created_at DESC');
        $condition = $this->getQuery();
        if (isset($condition['status'])) {
            $collection->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [], 'left')
                    ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left');
            if ($condition['status'] == 1) {
                $collection->where('(sales_order_phase.code=\'pending_payment\' OR sales_order_phase.code=\'pending\')');
            } elseif ($condition['status'] == 2) {
                $collection->where([
                    'sales_order_phase.code' => 'complete',
                    'sales_order_status.is_default' => 1
                ]);
            } elseif ($condition['status'] == 3) {
                $reviews = new Review();
                $reviews->columns(['order_id'])
                        ->where(['review.customer_id' => $this->getCustomer()->getId()])
                ->where->isNotNull('review.order_id');
                $collection->notIn('sales_order.id', $reviews)
                        ->where([
                            'sales_order_phase.code' => 'complete',
                            'sales_order_status.is_default' => 0
                        ]);
            }
        }
        unset($condition['status']);
        //echo $collection->getSqlString($this->getContainer()->get('dbAdapter')->getPlatform());exit;
        $select = $collection->getSelect();
        $this->filter($select, $condition);
        return $collection;
    }

    public function getLatestOrder()
    {
        $orders = $this->getOrders();
        return $orders->count() ? $orders[0] : false;
    }
}
