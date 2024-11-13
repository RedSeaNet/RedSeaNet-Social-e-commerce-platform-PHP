<?php

namespace Redseanet\RewardPoints\Mq;

use Redseanet\Lib\Mq\MqInterface;
use Redseanet\RewardPoints\Model\Collection\Record as Collection;
use Redseanet\RewardPoints\Model\Record;
use Redseanet\Sales\Model\Order;
use Redseanet\Lib\Bootstrap;

class Using implements MqInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\RewardPoints\Traits\Calc;

    public function apply($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        $count = $event['count'] ?: false;
        if ($config['rewardpoints/general/enable'] && $config['rewardpoints/using/rate'] && $model->offsetGet('customer_id')) {
            $additional = $model['additional'] ? json_decode($model['additional'], true) : [];
            $points = $this->getPoints($model);
            $additional['rewardpoints'] = $count === false ? $points : min($count, $points);
            $model->setData(['additional' => json_encode($additional)]);
        }
    }

    public function cancel($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['rewardpoints/general/enable'] && $config['rewardpoints/using/rate'] && $model->offsetGet('customer_id')) {
            $additional = $model['additional'] ? json_decode($model['additional'], true) : [];
            unset($additional['rewardpoints']);
            $model->setData(['additional' => json_encode($additional)]);
        }
    }

    public function calc($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['rewardpoints/general/enable'] && $config['rewardpoints/using/rate'] && $model->offsetGet('customer_id')) {
            $additional = $model['additional'] ? json_decode($model['additional'], true) : [];
            if (!empty($additional['rewardpoints'])) {
                $points = $this->getPoints($model, true);
                $additional['rewardpoints'] = min($additional['rewardpoints'], $points);
                $discount = function_exists('bcmul') ? bcmul($additional['rewardpoints'], $config['rewardpoints/using/rate'], 4) : $additional['rewardpoints'] * $config['rewardpoints/using/rate'];
                $model->setData([
                    'additional' => json_encode($additional),
                    'base_discount' => (float) $model->offsetGet('base_discount') - $discount,
                    'discount_detail' => json_encode([$config['rewardpoints/general/title'] => -$discount] + (json_decode($model['discount_detail'], true) ?: []))
                ])->setData('discount', $model->getCurrency()->convert($model->offsetGet('base_discount')));
            }
        }
    }

    public function afterOrderPlace($data)
    {
        $config = $this->getContainer()->get('config');
        for ($i = 0; $i < count($data['ids']); $i++) {
            $model = new Order();
            $model->load($data['ids'][$i]);

            if ($config['rewardpoints/general/enable'] && $config['rewardpoints/using/rate'] && $model->offsetGet('customer_id')) {
                $points = $model->getAdditional('rewardpoints');
                //Bootstrap::getContainer()->get('log')->logException(new \Exception('using points:'.$points));
                if ($points && $model['base_discount'] < (json_decode($model['discount_detail'], true)['Promotion'] ?? 0)) {
                    $record = new Record([
                        'customer_id' => $model->offsetGet('customer_id'),
                        'order_id' => $model->getId(),
                        'count' => -$points,
                        'status' => 1,
                        'comment' => 'Consumption'
                    ]);
                    $record->save();
                }
            }
        }
    }

    public function afterRefund($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        $order = $model->getOrder();
        if ($event['isNew'] && $config['rewardpoints/general/enable'] && $config['rewardpoints/using/refund'] && $order && $order['additional']) {
            $additional = json_decode($order['additional'], true);
            if (!empty($additional['rewardpoints'])) {
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

    public function afterOrderCancel($event)
    {
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
