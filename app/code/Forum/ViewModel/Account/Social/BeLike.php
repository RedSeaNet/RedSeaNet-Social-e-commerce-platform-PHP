<?php

namespace Redseanet\Forum\ViewModel\Account\Social;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Forum\Model\Post\Like;

class BeLike extends Template
{
    public function clearNewBeLikeCount($customer_id = null)
    {
        $customer_id = $customer_id ?? (new Segment('customer'))->get('customer')['id'];
        $like = new Like();
        $clear = $like->clearNewBeLike($customer_id);
        return $clear;
    }
}
