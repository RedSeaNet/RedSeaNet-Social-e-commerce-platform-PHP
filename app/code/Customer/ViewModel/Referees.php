<?php

namespace Redseanet\Customer\ViewModel;

use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;
use Redseanet\Lib\Session\Segment;

class Referees extends Account
{
    public function getReferee()
    {
        $segment = new Segment('customer');
        $customer = new Customer();
        $customer->load($segment->get('customer')['id'], 'id');
        $referee = new Customer();
        if ($customer['referer']) {
            $referee->load($customer['referer'], 'increment_id');
            return $referee;
        } else {
            return null;
        }
    }

    public function getReferrer()
    {
        $segment = new Segment('customer');
        $customer = $segment->get('customer');
        $referrer = new customerCollection();
        $referrer->where(['referer' => $customer['increment_id']]);
        return $referrer;
    }
}
