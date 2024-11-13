<?php

namespace Redseanet\RewardPoints\Traits;

use Redseanet\RewardPoints\Model\Collection\Record as Collection;
use Redseanet\Sales\Model\Cart;
use Laminas\Db\Sql\Expression;

trait Calc
{
    protected function getPoints($model, $withUsed = false)
    {
        $config = $this->getContainer()->get('config');
        $collection = new Collection();
        $collection->columns(['amount' => new Expression('sum(count)')])
                ->where([
                    'customer_id' => $model->offsetGet('customer_id'),
                    'status' => 1
                ]);
        $balance = (count($collection) ? $collection[0]['amount'] : 0) - $config['rewardpoints/using/remain'];
        $total = [];
        $unavailable = [];
        if ($model instanceof Cart) {
            foreach ($model->getItems() as $item) {
                if (!empty($item['status'])) {
                    if ($item['product']['can_use_reward_points']) {
                        if (!isset($total[$item['store_id']])) {
                            $total[$item['store_id']] = 0;
                        }
                        $total[$item['store_id']] += $item['base_price'] * $item['qty'];
                    } else {
                        if (!isset($unavailable[$item['store_id']])) {
                            $unavailable[$item['store_id']] = 0;
                        }
                        $unavailable[$item['store_id']] += $item['base_price'] * $item['qty'];
                    }
                }
            }
        } else {
            $storeId = $model['store_id'];
            $total[$storeId] = 0;
            $unavailable[$storeId] = 0;
            foreach ($model->getItems() as $item) {
                if ($item['product']['can_use_reward_points']) {
                    $total[$storeId] += $item['base_price'] * $item['qty'];
                } else {
                    $unavailable[$storeId] += $item['base_price'] * $item['qty'];
                }
            }
        }
        $minAmount = $config['rewardpoints/using/min_amount'];
        $maxAmount = $config['rewardpoints/using/max_amount'];
        $maxAmountCalc = $config['rewardpoints/using/max_amount_calculation'];
        $rate = $config['rewardpoints/using/rate'];
        $calculation = $config['rewardpoints/using/calculation'];
        $additional = $model['additional'] ? json_decode($model['additional'], true) : [];
        $discount = $model['base_discount'] + ($withUsed ? 0 : ($additional['rewardpoints'] ?? 0) * $rate);
        foreach ($total as $key => &$t) {
            $tmp = $t + ($unavailable[$key] ?? 0) ?
                    $t + (($calculation ? $model['base_shipping'] + $model['base_tax'] : 0) + $discount) * (function_exists('bcdiv') ? bcdiv($t, $t + ($unavailable[$key] ?? 0), 4) : $t / ($t + ($unavailable[$key] ?? 0))) : 0;
            $max = ($maxAmountCalc ? ((int) (function_exists('bcdiv') ? bcmul($tmp, bcdiv($maxAmount, 100, 4), 4) : $tmp * $maxAmount / 100)) : ((int) $maxAmount));
            if ($tmp >= $minAmount) {
                if ($max) {
                    $t = min($max, function_exists('bcdiv') ? bcdiv($tmp, $rate, 4) : $tmp / $rate);
                } else {
                    $t = function_exists('bcdiv') ? bcdiv($tmp, $rate, 4) : $tmp / $rate;
                }
            } else {
                $t = 0;
            }
        }
        return min($balance, array_sum($total));
    }
}
