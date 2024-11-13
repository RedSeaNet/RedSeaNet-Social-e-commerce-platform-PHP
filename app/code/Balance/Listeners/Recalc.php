<?php

namespace Redseanet\Balance\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Customer\Model\Balance;
use Redseanet\Customer\Model\Collection\Balance as Collection;
use Redseanet\Sales\Model\Collection\Order\Status\History;

class Recalc implements ListenerInterface
{
    use \Redseanet\Balance\Traits\Recalc;

    use \Redseanet\Lib\Traits\Container;

    public function afterCustomerLogin($event)
    {
        $customer = $event['model'];
        $this->recalc($customer->getId());
    }

    public function afterOrderPlace($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['balance/general/enable'] && $config['balance/general/product_for_recharge'] && $model->offsetGet('customer_id')) {
            foreach ($model->getItems(true) as $item) {
                if ($item['product_id'] == $config['balance/general/product_for_recharge']) {
                    $recharge = new Balance([
                        'customer_id' => $model->offsetGet('customer_id'),
                        'order_id' => $model->getId(),
                        'amount' => $item['qty'],
                        'comment' => 'Recharge Product',
                        'status' => 0
                    ]);
                    $recharge->save();
                }
            }
        }
    }

    public function afterOrderComplete($event)
    {
        $model = $event['model'];
        if ($model->getPhase()['code'] === 'complete') {
            $history = new History();
            $history->join('sales_order_status', 'sales_order_status.id=sales_order_status_history.status_id', [], 'left')
                    ->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [], 'left')
                    ->where([
                        'order_id' => $model->getId(),
                        'sales_order_phase.code' => 'complete'
                    ]);
            if (count($history->load(false, true)) === 0) {
                $collection = new Collection();
                $collection->columns(['id'])
                        ->where(['order_id' => $model->getId()])
                ->where->greaterThan('amount', 0);
                if (count($collection)) {
                    $record = new Balance();
                    $record->load($collection[0]['id']);
                    $record->setData('status', 1)->save();
                }
            }
        }
    }
}
