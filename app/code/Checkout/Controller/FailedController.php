<?php

namespace Redseanet\Checkout\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;

class FailedController extends ActionController
{
    public function indexAction()
    {
        $segment = new Segment('checkout');
        if ($segment->get('hasNewOrder')) {
            return $this->getLayout('checkout_order_failed');
        }
        return $this->redirectReferer('checkout/cart/');
    }
}
