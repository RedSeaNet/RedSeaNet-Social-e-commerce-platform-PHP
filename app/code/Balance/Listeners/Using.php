<?php

namespace Redseanet\Balance\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Customer\Model\Balance;
use Redseanet\Customer\Model\Collection\Balance as Collection;

class Using implements ListenerInterface {

    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Balance\Traits\Calc;
    use \Redseanet\Balance\Traits\Recalc;

    public function apply($event) {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['balance/general/enable'] && $config['balance/general/product_for_recharge'] && $model->offsetGet('customer_id')) {
            $discountDetail = $model->offsetGet('discount_detail') ? json_decode($model->offsetGet('discount_detail'), true) : [];
            $discountDetail["balance"] = ["total" => 0, "detail" => []];
            $model->setData([
                'discount_detail' => json_encode($discountDetail), "use_balance" => 1
            ]);
        }
    }

    public function cleanBalance($event) {
        $model = $event['model'];
        $discountDetail = $model->offsetGet('discount_detail') ? json_decode($model->offsetGet('discount_detail'), true) : [];
        if ($model["use_balance"] == 1) {
            unset($discountDetail['balance']);
            $model->setData([
                'discount_detail' => json_encode($discountDetail),
                "use_balance" => 0
            ]);
        }
    }

    public function calc($event) {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['balance/general/enable'] && $config['balance/general/product_for_recharge'] && $model->offsetGet('customer_id') && $model["use_balance"] == 1) {
            $discountDetail = $model['discount_detail'] ? json_decode($model['discount_detail'], true) : [];
            $evaluableBalance = $this->getBalances($model, true);
            $balances = min(($model["base_subtotal"] - (!empty($discountDetail["promotion"]["total"]) ? (float) $discountDetail["promotion"]["total"] : 0) - (!empty($discountDetail["rewardpoints"]["total"]) ? (float) $discountDetail["rewardpoints"]["total"] : 0)), $evaluableBalance);
            $items = $model->getItems();
            $totals = [];
            $total = 0;
            foreach ($items as $item) {
                if (empty($totals[$item['store_id']])) {
                    $totals[$item['store_id']] = 0;
                }
                $totals[$item['store_id']] += $item['base_total'] * $item['qty'];
                $total += $item['base_total'] * $item['qty'];
            }
            $discountDetail["balance"] = [];
            $discountDetail["balance"]["detail"] = [];
            $discountDetail["balance"]["store_total"] = [];
            foreach ($totals as $store => $tore_total) {
                $discountDetail["balance"]["detail"][$store] = $balances * ($tore_total / $total);
                $discountDetail["balance"]["store_total"][$store] = $balances * ($tore_total / $total);
            }
            $discountDetail["balance"]["total"] = $balances;
            $model->setData([
                'base_discount' => (float) $model->offsetGet('base_discount') - $balances,
                'discount_detail' => json_encode($discountDetail),
            ])->setData('discount', $model->getCurrency()->convert($model->offsetGet('base_discount')));
        }
    }

    public function cancel($event) {
        $model = $event['model'];
        $discountDetail = $model->offsetGet('discount_detail') ? json_decode($model->offsetGet('discount_detail'), true) : [];
        if ($model["use_balance"] == 1) {
            unset($discountDetail['balance']);
            $model->setData([
                'discount_detail' => json_encode($discountDetail),
                "use_balance" => 0
            ]);
        }
    }

    public function afterOrderPlace($event) {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        $discountDetail = json_decode($model['discount_detail'], true);
        if ($config['balance/general/enable'] && $config['balance/general/product_for_recharge'] && $model->offsetGet('customer_id')) {
            $balances = (float) (!empty($discountDetail["balance"]["total"]) ? $discountDetail["balance"]["total"] : 0);
            if ($balances && $balances > 0) {
                $record = new Balance([
                    'customer_id' => $model->offsetGet('customer_id'),
                    'order_id' => $model->getId(),
                    'amount' => -$balances,
                    'status' => 1,
                    'comment' => 'Consumption'
                ]);
                $record->save();
            }
        }
    }

    public function afterRefund($event) {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        $order = $model->getOrder();
        if ($event['isNew'] && $config['balance/general/enable'] && $order && $order['discount_detail']) {
            $discountDetail = json_decode($order['discount_detail'], true);
            if (!empty($discountDetail['balance'])) {
                $collection = new Collection();
                $collection->columns(['customer_id', 'order_id', 'amount'])
                        ->where(['order_id' => $order->getId()])
                ->where->notEqualTo('comment', 'Order Refund');
                foreach ($collection as $record) {
                    $record->setData([
                        'comment' => 'Order Refund',
                        'status' => 1
                    ])->save();
                }
            }
        }
    }

    public function afterOrderCancel($event) {
        $model = $event['model'];
        if ($model->getPhase()['code'] === 'canceled') {
            $collection = new Collection();
            $collection->columns(['id'])
                    ->where(['order_id' => $model->getId()])
            ->where->lessThan('amount', 0);
            if (count($collection)) {
                $record = new Balance();
                $record->load($collection[0]['id']);
                $record->setData(['comment' => 'Order Cancelled', 'status' => 0])->save();
            }
        }
    }

}
