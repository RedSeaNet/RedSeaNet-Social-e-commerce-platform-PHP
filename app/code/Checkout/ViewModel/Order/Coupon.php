<?php

namespace Redseanet\Checkout\ViewModel\Order;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Promotion\Model\Collection\Rule;
use Redseanet\Sales\Model\Cart;
use Redseanet\Lib\Session\Segment;

class Coupon extends Template
{
    public function hasLoggedIn()
    {
        $segment = new Segment('customer');
        return $segment->get('hasLoggedIn');
    }

    public function getCurrent()
    {
        $couponObject = Cart::instance()->offsetGet('coupon');
        if (!empty($couponObject)) {
            $coupons = $this->getVariable('store') ? json_decode($couponObject, true) : [];
            return $coupons[$this->getVariable('store')] ?? '';
        } else {
            return '';
        }
    }

    public function getCoupons()
    {
        $time = time();
        $rules = new Rule();
        $rules->withStore(true)
                ->where(['use_coupon' => 1, 'status' => 1])
                ->order('sort_order');
        $result = [];
        $storeId = $this->getVariable('store');
        foreach ($rules as $rule) {
            $condition = $rule->getCondition();
            if ((
                empty($rule->offsetGet('store_id')) ||
                    in_array($storeId, (array) $rule->offsetGet('store_id'))
            ) &&
            (empty($rule->offsetGet('from_date')) || $time >= strtotime($rule->offsetGet('from_date'))) &&
            (empty($rule->offsetGet('to_date')) || $time <= strtotime($rule->offsetGet('to_date'))) &&
            (empty($condition) ||
            $condition->match(Cart::instance(), $storeId))) {
                foreach ($rule->getCoupon() as $coupon) {
                    if ($rule->matchCoupon($coupon['code'], Cart::instance())) {
                        $result[] = [
                            'code' => $coupon->offsetGet('code'),
                            'title' => $rule->offsetGet('name')
                        ];
                        break;
                    }
                }
            }
        }
        return $result;
    }
}
