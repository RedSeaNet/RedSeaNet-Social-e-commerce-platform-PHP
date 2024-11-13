<?php

namespace Redseanet\Sales\ViewModel\Refund;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Source\Refund\Reason;
use Redseanet\Sales\Source\Refund\Service;

class Apply extends Template
{
    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }

    public function getReasons()
    {
        return (new Reason())->getSourceArray();
    }

    public function getServices()
    {
        $services = (new Service())->getSourceArray();
        $order = $this->getVariable('model');
        return $order->getPhase()['code'] === 'complete' ? $services : [$services[0]];
    }

    public function getItems()
    {
        $order = $this->getVariable('model');
        $items = [];
        foreach ($order->getItems() as $item) {
            $items[$item->getId()] = $item;
        }
        $memos = $order->getCreditMemo();
        foreach ($memos as $memo) {
            foreach ($memo->getItems() as $item) {
                $items[$item['item_id']]['qty'] -= $item['qty'];
            }
        }
        return $items;
    }
}
