<?php

namespace Redseanet\Customer\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Sales\Model\Order;

class Balance extends AbstractModel
{
    use \Redseanet\Balance\Traits\Recalc;

    protected function construct()
    {
        $this->init('customer_balance', 'id', ['id', 'customer_id', 'order_id', 'amount', 'comment', 'status', 'additional']);
    }

    public function getOrder()
    {
        if (!empty($this->storage['order_id'])) {
            $order = new Order();
            $order->load($this->storage['order_id']);
            return $order;
        }
        return null;
    }

    protected function afterSave()
    {
        if ($this->storage['status']) {
            $this->recalc($this->storage['customer_id']);
        }
        parent::afterSave();
    }
}
