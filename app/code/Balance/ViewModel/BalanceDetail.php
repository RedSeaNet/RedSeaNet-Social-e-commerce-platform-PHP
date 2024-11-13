<?php

namespace Redseanet\Balance\ViewModel;

use Redseanet\Customer\ViewModel\Account;
use Redseanet\Customer\Model\Balance\Draw;
use Redseanet\Customer\Model\Collection\Balance;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Expression;

class BalanceDetail extends Account
{
    protected $customerId = null;

    public function getCustomerId()
    {
        if (is_null($this->customerId)) {
            $customer = $this->getVariable('customer');
            if ($customer) {
                $this->customerId = $customer->getId();
            } else {
                $segment = new Segment('customer');
                $this->customerId = $segment->get('customer')['id'];
            }
        }
        return $this->customerId;
    }

    public function getStatement()
    {
        if ($this->getCustomerId()) {
            $balance = new Balance();
            $balance->join('sales_order', 'sales_order.id=customer_balance.order_id', ['increment_id'], 'left')
                    ->where(['customer_balance.customer_id' => $this->getCustomerId()])
                    ->order('customer_balance.created_at DESC')
                    ->limit(20)
                    ->offset(((int) $this->getQuery('page', 1) - 1) * 20);
            if (count($balance)) {
                return $balance;
            }
        }
        return [];
    }

    public function getAmount()
    {
        if ($this->getCustomerId()) {
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

    public function getDrawStatus($record)
    {
        if ($record['comment'] === 'Draw' && $record['additional']) {
            $draw = new Draw();
            $draw->load($record['additional']);
            switch ((int) $draw['status']) {
                case 0: return 'Processing';
                case 1: return 'Complete';
                case -1: return 'Canceled';
            }
        }
        return false;
    }
}
