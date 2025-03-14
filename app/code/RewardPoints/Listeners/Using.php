<?php

namespace Redseanet\RewardPoints\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\RewardPoints\Model\Collection\Record as Collection;
use Redseanet\RewardPoints\Model\Record;

class Using implements ListenerInterface {

    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\RewardPoints\Traits\Calc;

    public function apply($event) {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        $count = $event['count'] ?: '';
        if ($config['rewardpoints/general/enable'] && $config['rewardpoints/using/rate'] && $model->offsetGet('customer_id')) {
            $discountDetail = $model->offsetGet('discount_detail') ? json_decode($model->offsetGet('discount_detail'), true) : [];
            $evaluablePoints = $this->getPoints($model);
            $maxPoint = ($model["base_subtotal"] - (!empty($discountDetail["promotion"]["total"]) ? (float) $discountDetail["promotion"]["total"] : 0)) / $config['rewardpoints/using/rate'];
            $discountDetail["rewardpoints"] = ["total" => (min($maxPoint, (empty($count) ? $evaluablePoints : min($count, $evaluablePoints)))) * $config['rewardpoints/using/rate']];
            $model->setData(['discount_detail' => json_encode($discountDetail), "use_reward_point" => 1]);
        }
    }

    public function cancel($event) {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['rewardpoints/general/enable'] && $config['rewardpoints/using/rate'] && $model->offsetGet('customer_id') && $model["use_reward_point"] == 1) {
            $discountDetail = $model->offsetGet('discount_detail') ? json_decode($model->offsetGet('discount_detail'), true) : [];
            unset($discountDetail['rewardpoints']);
            $model->setData(['discount_detail' => json_encode($discountDetail), "use_reward_point" => 0]);
        }
    }

    public function calc($event) {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['rewardpoints/general/enable'] && $config['rewardpoints/using/rate'] && $model->offsetGet('customer_id') && $model["use_reward_point"] == 1) {
            $discountDetail = !empty($model['discount_detail']) ? json_decode($model['discount_detail'], true) : [];
            $discountDetail['rewardpoints']["total"] = ($discountDetail['rewardpoints']["total"] ?? 0);
            $evaluablePoints = $this->getPoints($model, true);
            $maxPoint = ($model["base_subtotal"] - (!empty($discountDetail["promotion"]["total"]) ? (float) $discountDetail["promotion"]["total"] : 0)) / $config['rewardpoints/using/rate'];
            $points = 0;
            if (!empty($discountDetail["rewardpoints"]["total"]) && $discountDetail["rewardpoints"]["total"] > 0) {
                $points = min($maxPoint, min($discountDetail["rewardpoints"]["total"] / $config['rewardpoints/using/rate'], $evaluablePoints));
            } else {
                $points = min($evaluablePoints, $points);
            }
            $discountDetail['rewardpoints']["total"] = $points * $config['rewardpoints/using/rate'];
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
            $discountDetail["rewardpoints"]["detail"] = [];
            $discountDetail["rewardpoints"]["store_total"] = [];

            foreach ($totals as $store => $tore_total) {
                $discountDetail["rewardpoints"]["detail"][$store] = $discountDetail['rewardpoints']["total"] * ($tore_total / $total);
                $discountDetail["rewardpoints"]["store_total"][$store] = $discountDetail['rewardpoints']["total"] * ($tore_total / $total);
            }
            $model->setData([
                'base_discount' => (float) $model->offsetGet('base_discount') - $discountDetail['rewardpoints']["total"],
                'discount_detail' => json_encode($discountDetail)
            ])->setData('discount', $model->getCurrency()->convert($model->offsetGet('base_discount')));
        }
    }

    public function afterOrderPlace($event) {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        $discountDetail = (!empty($model["discount_detail"]) ? json_decode($model["discount_detail"], true) : []);
        if ($config['rewardpoints/general/enable'] && $config['rewardpoints/using/rate'] && $model->offsetGet('customer_id')) {
            $points = (!empty($discountDetail["rewardpoints"]["total"]) ? $discountDetail["rewardpoints"]["total"] : 0);
            if ($points > 0) {
                $record = new Record([
                    'customer_id' => $model->offsetGet('customer_id'),
                    'order_id' => $model->getId(),
                    'count' => -($points / $config['rewardpoints/using/rate']),
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
        if ($event['isNew'] && $config['rewardpoints/general/enable'] && $config['rewardpoints/using/refund'] && $order && $order['discount_detail']) {
            $discountDetail = json_decode($order['discount_detail'], true);
            if (!empty($discountDetail['rewardpoints'])) {
                $collection = new Collection();
                $collection->columns(['customer_id', 'order_id', 'amount'])
                        ->where(['order_id' => $order->getId()])
                        ->where->lessThan('count', 0)
                        ->notEqualTo('comment', 'Order Refund');
                $rate = min(($model['base_total'] - max($model['base_discount'], $order->getDiscount($config['rewardpoints/general/title']))) / ($order['base_total'] - $order->getDiscount($config['rewardpoints/general/title'])), 1);
                foreach ($collection as $record) {
                    $amount = 0 - $record['amount'] * $rate;
                    $record->setData([
                        'amount' => $amount,
                        'comment' => 'Order Refund',
                        'status' => 1
                    ]);
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
            ->where->lessThan('count', 0);
            if (count($collection)) {
                $record = new Record();
                $record->load($collection[0]['id']);
                $record->setData(['comment' => 'Order Cancelled', 'status' => 0])->save();
            }
        }
    }

}
