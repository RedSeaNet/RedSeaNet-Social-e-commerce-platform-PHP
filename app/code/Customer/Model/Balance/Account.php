<?php

namespace Redseanet\Customer\Model\Balance;

use Redseanet\Lib\Model\AbstractModel;

class Account extends AbstractModel
{
    protected function construct()
    {
        $this->init('customer_balance_draw_account', 'id', ['id', 'customer_id', 'type', 'detail']);
    }
}
