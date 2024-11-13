<?php

namespace Redseanet\Customer\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;

class Welcome extends Template
{
    public function __construct()
    {
        $this->setTemplate('customer/welcome');
    }

    public function getCustomer()
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn')) {
            return $segment->get('customer');
        }
        return false;
    }
}
