<?php

namespace Redseanet\Retailer\ViewModel\Sales\Edit;

use Redseanet\Retailer\ViewModel\Sales\View\Order;
use Redseanet\Sales\Model\Collection\CreditMemo\Item;
use Redseanet\Sales\Model\Collection\Rma\Item as RmaItem;
use Laminas\Db\Sql\Expression;

class CreditMemo extends Order
{
    protected $items = null;

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

    public function getQty($itemId, $default)
    {
        if (is_null($this->items)) {
            $this->items = [];
            if ($id = $this->getQuery('rma_id')) {
                $items = new RmaItem();
                $items->where(['rma_id' => $id]);
                foreach ($items as $item) {
                    $this->items[$item['item_id']] = $item['qty'];
                }
            }
        }
        return empty($this->items) ? $default : ($this->items[$itemId] ?? 0);
    }
}
