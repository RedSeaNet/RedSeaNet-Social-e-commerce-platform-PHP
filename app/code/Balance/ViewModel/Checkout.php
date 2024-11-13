<?php

namespace Redseanet\Balance\ViewModel;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart;

class Checkout extends Template
{
    use \Redseanet\Balance\Traits\Calc;

    public function getCurrentBalances()
    {
        $segment = $this->getSegment('customer');
        if ($segment->get('hasLoggedIn')) {
            $customer = new Customer();
            $customer->load($segment->get('customer')['id']);
            return (float) $customer->getBalance();
        }
        return 0;
    }

    public function getCurrency()
    {
        return Cart::instance()->getCurrency();
    }

    public function getAvailableBalances()
    {
        if ($this->getSegment('customer')->get('hasLoggedIn')) {
            return $this->getBalances(Cart::instance());
        }
        return 0;
    }

    public function canUse()
    {
        $flag = $this->getConfig()['balance/general/enable'];
        if ($flag && ($id = $this->getConfig()['balance/general/product_for_recharge'])) {
            foreach (Cart::instance()->getItems() as $item) {
                if ($id === $item['product_id']) {
                    $flag = false;
                }
            }
        } else {
            $flag = false;
        }
        return $flag && $this->getSegment('customer')->get('hasLoggedIn');
    }

    public function hasApplied()
    {
        $additional = Cart::instance()->offsetGet('additional');
        return $additional && !empty(@json_decode($additional, true)['balance']);
    }
}
