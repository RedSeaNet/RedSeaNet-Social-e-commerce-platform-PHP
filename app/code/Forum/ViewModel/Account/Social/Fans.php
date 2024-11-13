<?php

namespace Redseanet\Forum\ViewModel\Account\Social;

use Redseanet\Forum\Model\CustomerLike;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;

class Fans extends Template
{
    public function clearNewFansCount($customer_id = null)
    {
        $customer_id = $customer_id ?? (new Segment('customer'))->get('customer')['id'];
        $like = new CustomerLike();
        $clear = $like->clearNewFans($customer_id);
        return $clear;
    }
}
