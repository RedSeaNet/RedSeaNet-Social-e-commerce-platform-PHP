<?php

namespace Redseanet\Retailer\ViewModel\Dashboard;

use Redseanet\Retailer\ViewModel\AbstractViewModel;

class Stat extends AbstractViewModel
{
    public function getStat()
    {
        return $this->getConfig()['stat'];
    }
}
