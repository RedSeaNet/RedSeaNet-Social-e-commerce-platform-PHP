<?php

namespace Redseanet\Checkout\ViewModel\Order;

use Redseanet\Checkout\ViewModel\Cart;

class Review extends Cart
{
    public function getRow($item, $rowspan = 0)
    {
        $row = $this->getChild('item');
        $row->setVariable('item', $item)
                ->setVariable('rowspan', $rowspan);
        return $row;
    }

    public function getItems()
    {
        $items = parent::getItems();
        $result = [];
        foreach ($items as $item) {
            if ($item['status']) {
                $result[] = $item;
            }
        }
        usort($result, function ($a, $b) {
            return $a['store_id'] <=> $b['store_id'];
        });
        return $result;
    }
}
