<?php

namespace Redseanet\RewardPoints\Mq;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Mq\MqInterface;
use Redseanet\RewardPoints\Model\Collection\Record as Collection;
use Redseanet\RewardPoints\Model\Record;
use Redseanet\Sales\Model\Collection\Order\Status\History;
use Laminas\Db\Sql\Expression;
use Redseanet\Sales\Model\Order;
use Redseanet\Lib\Bootstrap;

class Gather implements MqInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\RewardPoints\Traits\Recalc;

    use \Redseanet\Lib\Traits\DataCache;

    public function afterReview($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['rewardpoints/general/enable'] &&
                ($points = $config['rewardpoints/gathering/reviewing']) &&
                $event['isNew'] && $model->offsetGet('customer_id') &&
                $model->offsetGet('order_id')) {
            $limits = $config['rewardpoints/gathering/words_limitation'];
            if (!$limits || count(explode(' ', preg_replace('/[^\x00-\x7F]{3}/', ' ', preg_replace('/\s+/', ' ', trim(@gzdecode($model->offsetGet('content'))))))) > $limits) {
                $record = new Record([
                    'customer_id' => $model->offsetGet('customer_id'),
                    'count' => $points,
                    'comment' => 'Reviewing Product',
                    'status' => 1
                ]);
                $record->save();
            }
        }
    }

    public function afterRegister($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($config['rewardpoints/general/enable'] && ($points = $config['rewardpoints/gathering/registration']) && $event['isNew']) {
            $record = new Record([
                'customer_id' => $model->getId(),
                'count' => $points,
                'comment' => 'Registration',
                'status' => 1
            ]);
            $record->save();
        }
    }

    private function getPoints($order)
    {
        $config = $this->getContainer()->get('config');
        $total = 0;
        $unavailable = 0;
        $points = 0;
        $items = $order->getItems();
        Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($items)));
        foreach ($items as $item) {
            if ($item['reward_points'] > 0) {
                $points += $item['reward_points'] * $item['qty'];
            } elseif (is_null($item['reward_points']) || $item['reward_points'] === '') {
                $total += $item['base_price'] * $item['qty'];
            } else {
                $unavailable += $item['base_price'] * $item['qty'];
            }
        }
        //Bootstrap::getContainer()->get('log')->logException(new \Exception('total:'.$total));
        if ($total + $unavailable == 0) {
            return 0;
        }
        $calculation = 0;
        if ($config['rewardpoints/gathering/calculation']) {
            $calculation = $order['base_shipping'] + $order['base_tax'];
        }
        $base_discount = (float) $order['base_discount'];
        $total += (($calculation + $base_discount) * $total) / ($total + $unavailable);
        $max = $config['rewardpoints/gathering/max_amount_calculation'] ? ((int) ($total * $config['rewardpoints/gathering/max_amount'] / 100)) : ((int) $config['rewardpoints/gathering/max_amount']);
        $calc = $total * $config['rewardpoints/gathering/rate'] + $points;
        return $total >= $config['rewardpoints/gathering/min_amount'] ? ($max ? min($max, $calc) : $calc) : 0;
    }

    public function afterOrderPlace($data)
    {
        $config = $this->getContainer()->get('config');
        for ($i = 0; $i < count($data['ids']); $i++) {
            $model = new Order();
            $model->load($data['ids'][$i]);
            $points = $this->getPoints($model);
            //Bootstrap::getContainer()->get('log')->logException(new \Exception('point:'.$points));
            if ($config['rewardpoints/general/enable'] && $config['rewardpoints/gathering/rate'] && $model->offsetGet('customer_id') && $points > 0) {
                $record = new Record([
                    'customer_id' => $model->offsetGet('customer_id'),
                    'order_id' => $model->getId(),
                    'count' => $points,
                    'comment' => 'Consumption',
                    'status' => 0
                ]);
                $record->save();
            }
        }
    }

    public function afterOrderComplete($event)
    {
        if ($this->getContainer()->get('config')['rewardpoints/general/activating'] == 0) {
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
                    ->where->greaterThan('count', 0);
                    if (count($collection)) {
                        $record = new Record();
                        $record->load($collection[0]['id']);
                        $record->setData('status', 1)->save();
                    }
                }
            }
        }
    }

    public function afterSubscribe($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($event['isNew'] && $config['rewardpoints/general/enable'] && ($points = $config['rewardpoints/gathering/newsletter']) && $model['status'] === 1) {
            $customer = new Customer();
            $customer->load($model['email'], 'email');
            if ($customer->getId()) {
                $record = new Record([
                    'customer_id' => $customer->getId(),
                    'count' => $points,
                    'comment' => 'Newsletter Signup',
                    'status' => 1
                ]);
                $record->save();
            }
        }
    }

    public function afterShare($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        if ($event['isNew'] && $config['rewardpoints/general/enable'] && ($points = $config['rewardpoints/gathering/share'])) {
            $collection = new Collection();
            $collection->columns(['amount' => new Expression('sum(count)')])
                    ->where([
                        'customer_id' => $model->offsetGet('customer_id'),
                        'status' => 1,
                        'comment' => 'Sharing to Social Medias'
                    ])->group('comment');
            if (!($limit = $config['rewardpoints/gathering/share_limitation']) ||
                    !count($collection->load(true, true)) ||
                    $collection[0]['amount'] < $limit) {
                $record = new Record([
                    'customer_id' => $model->offsetGet('customer_id'),
                    'count' => $limit ? min($limit - $collection[0]['amount'], $points) : $points,
                    'comment' => 'Sharing to Social Medias',
                    'status' => 1
                ]);
                $record->save();
            }
        }
    }

    public function afterRefund($event)
    {
        $config = $this->getContainer()->get('config');
        $model = $event['model'];
        $order = $model->getOrder();
        if ($config['rewardpoints/general/enable'] && $config['rewardpoints/gathering/refund'] && $order) {
            $tableGateway = $this->getTableGateway('reward_points');
            $update = $tableGateway->getSql()->update();
            $select = $tableGateway->getSql()->select();
            $select->columns(['customer_id'])
                    ->where(['order_id' => $order->getId(), 'status' => 1])
            ->where->greaterThan('count', 0);
            $result = $tableGateway->selectWith($select)->toArray();
            $customers = [];
            foreach ($result as $item) {
                $customers[] = $item['customer_id'];
            }
            $update->set(['status' => -1, 'comment' => 'Order Refund'])
                    ->where(['order_id' => $order->getId(), 'status' => 1])
            ->where->greaterThan('count', 0);
            $tableGateway->updateWith($update);
            $this->recalc($customers);
            $this->deleteCache('reward_points');
        }
    }

    public function beforeSaveCustomer($event)
    {
        unset($event['model']['rewardpoints']);
    }

    public function afterSaveBackendCustomer($event)
    {
        $customer = $event['model'];
        if ($count = (int) $customer->offsetGet('adjust_rewardpoints')) {
            $record = new Record();
            $record->setData([
                'customer_id' => $customer->getId(),
                'count' => $count,
                'comment' => 'System Adjustment',
                'status' => 1
            ]);
            $record->save();
        }
    }
}
