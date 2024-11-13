<?php

namespace Redseanet\Customer\Listeners;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Session\Segment;

class Validate implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function validate()
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn', false)) {
            $customer = new Customer();
            $customerSession = $segment->get('customer');
            $customer->load($customerSession['id']);
            if (!$customer->getId() || !$customer['status']) {
                $segment->clear();
            }
        }
    }
}
