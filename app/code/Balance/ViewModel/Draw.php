<?php

namespace Redseanet\Balance\ViewModel;

use Redseanet\Balance\Source\DrawType;
use Redseanet\Customer\Model\Collection\Balance;
use Redseanet\Customer\Model\Collection\Balance\Account;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Expression;

class Draw extends Template
{
    protected $customerId = null;
    protected $type = null;

    public function getCustomerId()
    {
        if (is_null($this->customerId)) {
            $segment = new Segment('customer');
            $this->customerId = $segment->get('customer')['id'];
        }
        return $this->customerId;
    }

    public function getType()
    {
        if (is_null($this->type)) {
            $this->type = (new DrawType())->getSourceArray();
        }
        return $this->type;
    }

    public function getAccount()
    {
        $account = new Account();
        $account->where(['customer_id' => $this->getCustomerId()]);
        return $account;
    }

    public function getBalance()
    {
        $balance = new Balance();
        $balance->columns(['amount' => new Expression('sum(amount)')])
                ->where([
                    'customer_id' => $this->getCustomerId(),
                    'status' => 1
                ]);
        $balance->load(false, true);
        $points = (count($balance) ? $balance[0]['amount'] : 0);
        return (float) $points;
    }
}
