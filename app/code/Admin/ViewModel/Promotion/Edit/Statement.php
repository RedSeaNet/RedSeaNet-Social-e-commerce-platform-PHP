<?php

namespace Redseanet\Admin\ViewModel\Promotion\Edit;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Promotion\Model\Collection\Coupon\Log as Collection;

class Statement extends Template
{
    protected $statement = null;

    public function getStatement()
    {
        if (is_null($this->statement)) {
            if ($this->getQuery('id')) {
                $this->statement = new Collection();
                $this->statement->join('promotion_coupon', 'promotion_coupon.id=promotion_coupon_log.coupon_id', ['code'], 'left')
                        ->join('sales_order', 'promotion_coupon_log.order_id=sales_order.id', ['increment_id'], 'left')
                        ->where([
                            'promotion_coupon.promotion_id' => $this->getQuery('id')
                        ])->limit(20)
                        ->offset(($this->getQuery('page', 1) - 1) * 20);
            } else {
                $this->statement = [];
            }
        }
        return $this->statement;
    }

    public function getCustomer($id)
    {
        $customer = new Customer();
        $customer->load($id);
        return $customer;
    }
}
