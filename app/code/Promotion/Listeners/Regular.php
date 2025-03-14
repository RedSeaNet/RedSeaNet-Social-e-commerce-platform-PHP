<?php

namespace Redseanet\Promotion\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Promotion\Model\Collection\Rule;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Store;
use Laminas\Db\Sql\Predicate\In;

class Regular implements ListenerInterface {

    use \Redseanet\Lib\Traits\Container;

    protected $model;
    protected $discount = 0;
    protected $items = [];
    protected $stores = [];

    public function calc($event) {
        $this->items = [];
        $this->discount = 0;
        $this->model = $event['model'];
        $storeIds = [];
        if ($this->model instanceof \Redseanet\Sales\Model\Cart) {
            foreach ($this->model->getItems(true) as $item) {
                if ($item['status']) {
                    if (!isset($this->items[$item->offsetGet('store_id')])) {
                        $this->items[$item->offsetGet('store_id')] = [];
                    }
                    if (!empty($item->offsetGet('store_id'))) {
                        $storeIds[] = $item->offsetGet('store_id');
                    }
                    $this->items[$item->offsetGet('store_id')][$item['id']] = $item;
                }
            }
        } else {
            foreach ($this->model->getItems(true) as $item) {
                if (!isset($this->items[$this->model->offsetGet('store_id')])) {
                    $this->items[$this->model->offsetGet('store_id')] = [];
                }
                if (!empty($item->offsetGet('store_id'))) {
                    $storeIds[] = $item->offsetGet('store_id');
                }
                $this->items[$this->model->offsetGet('store_id')][$item['id']] = $item;
            }
        }
        $storeIds = array_unique($storeIds);
        if (is_array($storeIds) && count($storeIds) > 0) {
            $storeCollection = new Store();
            $storeCollection->where(new In('id', $storeIds));
            //echo $storeCollection->getSqlString($this->getContainer()->get('dbAdapter')->getPlatform());
            $storeCollection->load(true, true);
            foreach ($storeCollection as $store) {
                $this->store[$store['id']] = $store;
            }
            //Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($this->store)));
            $result = 0;
            $result_store = [];
            $time = time();
            $rules = new Rule();
            $promotionDetail = [];
            $rules->withStore(true)
                    ->where(['status' => 1])
                    ->order('sort_order');
            $this->discount = -$this->model->offsetGet('base_discount');
            foreach ($this->items as $storeId => $i) {
                $block = false;
                foreach ($rules as $rule) {
                    if ((empty($rule->offsetGet('store_id')) ||
                            in_array($storeId, (array) $rule->offsetGet('store_id'))) &&
                            (empty($rule->offsetGet('from_date')) || $time >= strtotime($rule->offsetGet('from_date'))) &&
                            (empty($rule->offsetGet('to_date')) || $time <= strtotime($rule->offsetGet('to_date'))) &&
                            $this->matchRule($rule, $storeId)) {
                        if (!isset($promotionDetail[$storeId])) {
                            $promotionDetail[$storeId] = [];
                        }
                        $discount = $this->handleRule($rule, $storeId, $block);
                        $result += $discount;
                        if (!isset($result_store[$storeId])) {
                            $result_store[$storeId] = 0;
                        }
                        $result_store[$storeId] += $discount;
                        $promotionDetail[$storeId][] = $rule->toArray() + ['storeId' => $storeId, 'block' => $block, 'discount' => $discount, 'storename' => $this->store[$storeId]['name']];
                        $this->discount += $discount;
                        if ($block) {
                            $event->stopPropagation();
                            break;
                        }
                    }
                }
            }
            if ($result) {
                $base_discount = (float) $this->model->offsetGet('base_discount') - $result;
                $discount_detail = (!empty($this->model['discount_detail']) ? json_decode($this->model['discount_detail'], true) : []);
                $discount_detail["promotion"] = ['total' => -$result, 'store_total' => $result_store, 'detail' => $promotionDetail];
                $discount = $this->model->getCurrency()->convert($base_discount);
                $this->model->setData([
                    'base_discount' => $base_discount,
                    'discount_detail' => json_encode($discount_detail),
                    'discount' => $discount
                ]);
            }
        }
    }

    protected function matchRule($rule, $storeId) {
        if (!$rule['use_coupon'] || $rule->matchCoupon($this->model->getCoupon($storeId), $this->model)) {
            return $rule->getCondition() ? $rule->getCondition()->match($this->model, $storeId) : true;
        }
        return false;
    }

    protected function handleRule($rule, $storeId, &$block) {
        if ($rule['stop_processing']) {
            $block = true;
        }
        $handler = $rule->getHandler();
        $total = $this->model['base_subtotal'] + ($rule['apply_to'] ? 0 : (float) $this->model['base_shipping'] + (float) $this->model['base_tax']);
        $result = 0;
        if ($handler) {
            $items = $handler->matchItems($this->items[$storeId]);
        } else {
            $items = $this->items[$storeId];
        }
        if ($rule['free_shipping']) {
            $this->model->setData([
                'free_shipping' => 1,
                'base_shipping' => 0,
                'shipping' => 0
            ]);
        }
        $negative = 0;
        foreach ($items as $item) {
            $qty = $rule['qty'] ? min($rule['qty'], $item['qty']) : $item['qty'];
            $subtotal = $item['base_price'] * $qty + (float) ($item['base_tax'] ?? 0) + (float) ($item['base_discount'] ?? 0);
            $discount = $rule['is_fixed'] ? $rule['price'] : $subtotal * $rule['price'] / 100;
            if ($discount > $subtotal) {
                $negative += $discount - $subtotal;
                $discount = $subtotal;
            }
            $result += $discount;
        }
        if (!$rule['apply_to'] && $negative) {
            $result += min($negative, (float) $this->model['base_shipping'] + (float) $this->model['base_tax']);
        }
        return min($total - $this->discount, $result);
    }

}
