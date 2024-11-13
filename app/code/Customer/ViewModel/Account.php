<?php

namespace Redseanet\Customer\ViewModel;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;

class Account extends Template
{
    protected static $customer = null;
    protected static $currency = null;

    public function getCurrency()
    {
        if (is_null(self::$currency)) {
            self::$currency = $this->getContainer()->get('currency');
        }
        return self::$currency;
    }

    public function getCustomer()
    {
        if (is_null(self::$customer)) {
            $segment = new Segment('customer');
            self::$customer = new Customer();
            self::$customer->load($segment->get('customer')['id'], 'id');
        }
        return self::$customer;
    }
}
