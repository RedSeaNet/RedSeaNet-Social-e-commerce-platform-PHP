<?php

namespace Redseanet\Admin\ViewModel\Sales\Edit;

use Redseanet\Admin\ViewModel\Sales\View\Order;
use Redseanet\Sales\Model\Collection\Shipment\Item;
use Redseanet\Shipping\Source\Carrier;
use Laminas\Db\Sql\Expression;

class Shipment extends Order
{
    public function getCarriers()
    {
        return (new Carrier())->getSourceArray();
    }

    public function getMaxQty($item)
    {
        $items = new Item();
        $items->columns(['sum' => new Expression('sum(qty)')])
                ->group('item_id')
                ->having(['item_id' => $item->getId()]);
        if ($items->count()) {
            return $item['qty'] - $items[0]['sum'];
        }
        return $item['qty'];
    }
}
