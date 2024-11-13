<?php

namespace Redseanet\RewardPoints\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;

class Recalc implements ListenerInterface
{
    use \Redseanet\RewardPoints\Traits\Recalc;

    public function afterCustomerLogin($event)
    {
        $customer = $event['model'];
        $this->recalc($customer->getId());
    }
}
