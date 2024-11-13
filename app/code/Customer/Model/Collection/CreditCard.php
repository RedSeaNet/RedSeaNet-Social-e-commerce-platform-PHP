<?php

namespace Redseanet\Customer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class CreditCard extends AbstractCollection
{
    protected function construct()
    {
        $this->init('customer_credit_card');
    }
}
