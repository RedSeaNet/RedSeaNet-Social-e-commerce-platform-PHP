<?php

namespace Redseanet\Customer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Balance extends AbstractCollection
{
    protected function construct()
    {
        $this->init('customer_balance');
    }
}
