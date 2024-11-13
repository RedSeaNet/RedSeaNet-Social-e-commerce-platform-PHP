<?php

namespace Redseanet\Bulk\Listeners;

use Redseanet\Bulk\Model\Bulk;
use Redseanet\Bulk\Model\Collection\Bulk as Collection;
use Redseanet\Sales\Model\Collection\Order;
use Redseanet\Sales\Model\Collection\Order\Status;
use Redseanet\Sales\Model\Order\Status\History;

class Cron
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Bulk\Traits\Refund;

    public function schedule()
    {
        $this->unpaid();
        $this->expired();
    }

    private function expired()
    {
        $config = $this->getContainer()->get('config');
        $bulks = new Collection();
        $bulks->where(['status' => 1])
        ->where->lessThan('count', 'size', 'identifier', 'identifier');
        $expiration = [$config['catalog/bulk_sale/default_expiration']];
        $bulks->walk(function ($bulk) use ($expiration) {
            foreach ($bulk->getItems() as $item) {
                if (!empty($item['product']['bulk_expiration'])) {
                    $expiration[] = (int) $item['product']['bulk_expiration'];
                }
            }
            if (strtotime($bulk['created_at']) <= strtotime('-' . min($expiration) . ' days')) {
                $bulk->setData('status', 0)->save();
                $ids = $bulk->getOrderIds();
                if ($this->refund($ids)) {
                    $this->createCreditMemo($ids);
                }
            }
        });
    }

    private function unpaid()
    {
        $config = $this->getContainer()->get('config');
        $orders = new Order();
        $orders->columns(['id'])
                ->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [], 'left')
                ->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [], 'left')
                ->join('bulk_sale_member', 'bulk_sale_member.order_id=sales_order.id', ['bulk_id', 'member_id'], 'right')
                ->join('bulk_sale', 'bulk_sale.id=bulk_sale_member.bulk_id', ['count'], 'right')
                ->where([
                    'sales_order_phase.code' => ['pending', 'pending_payment']
                ])->where->lessThan('sales_order.created_at', date('Y-m-d H:i:s', strtotime('-' . $config['catalog/bulk_sale/time_for_payment'] . 'minutes')));
        $status = new Status();
        $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                ->where(['is_default' => 1, 'sales_order_phase.code' => 'closed'])
                ->limit(1);
        $dispatcher = $this->getContainer()->get('eventDispatcher');
        $statusId = $status[0]->getId();
        $statusName = $status[0]->offsetGet('name');
        $orders->walk(function ($order) use ($statusName, $statusId, $dispatcher) {
            $history = new History();
            $history->setData([
                'order_id' => $order['id'],
                'status_id' => $statusId,
                'status' => $statusName
            ])->save();
            $order->setData('status_id', $statusId)->save();
            $dispatcher->trigger('order.cancel.after', ['model' => $order]);
            $bulk = new Bulk();
            $bulk->setData([
                'id' => $order['bulk_id'],
                'count' => $order['count']
            ])->delMember($order['member_id']);
        });
    }
}
