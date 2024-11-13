<?php

namespace Redseanet\Checkout\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;

class Success extends Template
{
    public function hasLoggedIn()
    {
        return (bool) (new Segment('customer'))->get('hasLoggedIn');
    }
}
