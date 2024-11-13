<?php

namespace Redseanet\Sales\Model\Order\Status;

use Redseanet\Lib\Model\AbstractModel;

class History extends AbstractModel
{
    protected function construct()
    {
        $this->init('sales_order_status_history', 'id', [
            'id', 'admin_id', 'order_id', 'status_id', 'status',
            'is_customer_notified', 'is_visible_on_front', 'comment'
        ]);
    }
}
