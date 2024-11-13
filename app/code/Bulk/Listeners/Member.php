<?php

namespace Redseanet\Bulk\Listeners;

use Redseanet\Bulk\Model\Bulk;
use Redseanet\Lib\Listeners\ListenerInterface;

class Member implements ListenerInterface
{
    public function afterOrderPlaced($e)
    {
        $order = $e['model'];
        if ($e['isNew'] && $id = $order->getAdditional('bulk')) {
            $bulk = new Bulk();
            $bulk->load($id);
            $bulk->addMember($order['customer_id'], $order->getId());
        }
    }
}
