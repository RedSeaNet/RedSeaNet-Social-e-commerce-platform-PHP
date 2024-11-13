<?php

namespace Redseanet\Checkout\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;

class SuccessController extends ActionController
{
    public function indexAction()
    {
        $segment = new Segment('checkout');
        if ($segment->get('hasNewOrder')) {
            $segment->set('hasNewOrder', 0);
            return $this->getLayout('checkout_order_success');
        }
        return $this->redirectReferer('checkout/cart/');
    }
}
