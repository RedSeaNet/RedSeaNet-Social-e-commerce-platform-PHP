<?php

namespace Redseanet\RewardPoints\ViewModel;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart;

class Checkout extends Template
{
    use \Redseanet\RewardPoints\Traits\Calc;

    public function getCurrentPoints()
    {
        $segment = $this->getSegment('customer');
        if ($segment->get('hasLoggedIn')) {
            $customer = new Customer();
            $customer->load($segment->get('customer')['id']);
            return (int) $customer->offsetGet('rewardpoints');
        }
        return 0;
    }

    public function getAvailablePoints()
    {
        if ($this->getSegment('customer')->get('hasLoggedIn')) {
            return $this->getPoints(Cart::instance());
        }
        return 0;
    }

    public function hasApplied()
    {
        $additional = Cart::instance()->offsetGet('additional');
        return $additional && !empty(@json_decode($additional, true)['rewardpoints']);
    }

    public function canUse()
    {
        $flag = $this->getConfig()['rewardpoints/general/enable'] && $this->getConfig()['rewardpoints/using/rate'];
        if ($flag && ($id = $this->getConfig()['balance/general/product_for_recharge'])) {
            foreach (Cart::instance()->getItems() as $item) {
                if ($id === $item['product_id']) {
                    $flag = false;
                }
            }
        }
        return $flag && $this->getSegment('customer')->get('hasLoggedIn');
    }
}
