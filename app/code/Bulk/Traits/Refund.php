<?php

namespace Redseanet\Bulk\Traits;

use Exception;
use Redseanet\Customer\Model\Collection\Balance;
use Redseanet\Payment\Source\Method;
use Redseanet\Sales\Model\Collection\Order\Status as StatusCollection;
use Redseanet\Sales\Model\CreditMemo;
use Redseanet\Sales\Model\CreditMemo\Item;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Status\History;

trait Refund
{
    protected function refund($ids)
    {
        $config = $this->getContainer()->get('config');
        $methods = array_keys((new Method())->getSourceArray());
        $flag = false;
        foreach ($methods as $method) {
            if (is_callable([$config['payment/' . $method . '/model'], 'refund'])) {
                $method = new $config['payment/' . $method . '/model']();
                $flag = $flag || $method->refund($ids);
            }
        }
        if (!$flag) {
            $collection = new Balance();
            $collection->where([
                'order_id' => $ids,
                'comment' => 'Consumption'
            ])->where->lessThan('amount', 0);
            if (count($ids) === count($collection)) {
                $flag = true;
            }
        }
        return $flag;
    }

    protected function createCreditMemo($ids)
    {
        foreach ($ids as $id) {
            try {
                $order = new Order();
                $order->load($id);
                if (!$order->canRefund(false)) {
                    continue;
                }
                $memo = new CreditMemo();
                $memo->setData($order->toArray())->setData([
                    'increment_id' => '',
                    'order_id' => $id,
                    'comment' => ''
                ]);
                $this->beginTransaction();
                $memo->setId(null)->save();
                foreach ($order->getItems(true) as $item) {
                    $obj = new Item($item->toArray());
                    $obj->setData([
                        'id' => null,
                        'item_id' => $item->getId(),
                        'creditmemo_id' => $memo->getId()
                    ])->collateTotals()->save();
                }
                $memo->collateTotals()->save();
                $this->getContainer()->get('eventDispatcher')->trigger('order.refund.after', ['model' => $memo]);
                $order->setData([
                    'base_total_refunded' => (float) $order['base_total_refunded'] + $memo['base_total'],
                    'total_refunded' => (float) $order['total_refunded'] + $memo['total']
                ]);
                $status = new StatusCollection();
                $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [])
                        ->where(['is_default' => 1, 'sales_order_phase.code' => 'closed'])
                        ->limit(1);
                $order->setData('status_id', $status[0]->getId());
                $history = new History();
                $history->setData([
                    'order_id' => $order->getId(),
                    'status_id' => $status[0]->getId(),
                    'status' => $status[0]->offsetGet('name')
                ])->save();
                $order->save();
                $this->commit();
            } catch (Exception $e) {
                $this->rollback();
                $this->getContainer()->get('log')->logException($e);
            }
        }
    }
}
