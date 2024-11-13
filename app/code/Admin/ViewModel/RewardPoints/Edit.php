<?php

namespace Redseanet\Admin\ViewModel\RewardPoints;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\ViewModel\Template;

class Edit extends Template
{
    protected $customer = null;

    public function getCustomer()
    {
        if (is_null($this->customer) && $this->getQuery('id')) {
            $this->customer = new Customer();
            $this->customer->load($this->getQuery('id'));
        }
        return $this->customer;
    }

    public function getCurrentPoints()
    {
        if ($this->getCustomer()) {
            return (int) $this->getCustomer()->offsetGet('rewardpoints');
        }
        return 0;
    }
}
