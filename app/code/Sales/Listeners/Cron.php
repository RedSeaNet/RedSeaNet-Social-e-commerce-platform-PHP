<?php

namespace Redseanet\Sales\Listeners;

use Laminas\Db\Sql\Where;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Collection\Order\Status;
use Redseanet\Sales\Model\Collection\Order as Collection;
use Redseanet\Sales\Model\Order\Status\History;

class Cron
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    public function pudgeExpiredCart()
    {
        $where = new Where();
        $updated = new Where(null, Where::COMBINED_BY_OR);
        $updated->isNull('updated_at')->lessThanOrEqualTo('updated_at', date('Y-m-d H:i:s', strtotime('-7days')));
        $where->isNull('customer_id')
                ->lessThanOrEqualTo('created_at', date('Y-m-d H:i:s', strtotime('-7days')))
                ->addPredicate($updated);
        $this->getTableGateway('sales_cart')->delete($where);
    }

    public function pudgeExpiredOrder()
    {
        $status = new Status();
        $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                ->where(['is_default' => 1, 'sales_order_phase.code' => 'canceled'])
                ->limit(1);
        $count = 0;
        $userId = (new Segment('admin'))->get('user')->getId();
        $statusId = $status[0]->getId();
        $dispatcher = $this->getContainer()->get('eventDispatcher');
        $orders = new Collection();
        $orders->join('sales_order_status', 'sales_order.status_id=sales_order_status.id', [], 'left')
                ->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [], 'left')
                ->where(['sales_order_phase.code' => ['pending', 'pending_payment']])
        ->where->lessThanOrEqualTo('sales_order.created_at', date('Y-m-d H:i:s', strtotime('-2days')));
        foreach ($orders as $order) {
            $history = new History();
            $history->setData([
                'admin_id' => $userId,
                'order_id' => $order['id'],
                'status_id' => $statusId,
                'status' => $status[0]->offsetGet('name')
            ])->save();
            $order->setData('status_id', $statusId)
                    ->save();
            $dispatcher->trigger('order.cancel.after', ['model' => $order]);
            $count++;
        }
    }
}
