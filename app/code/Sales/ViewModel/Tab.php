<?php

namespace Redseanet\Sales\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Model\Collection;

class Tab extends Template
{
    protected $order = null;

    public function getOrder()
    {
        if (is_null($this->order)) {
            $this->order = $this->getVariable('order');
        }
        return $this->order;
    }

    public function getInvoice()
    {
        if ($this->hasVariable('order')) {
            $collection = new Collection\Invoice();
            $collection->where(['order_id' => $this->getOrder()->getId()]);
            return $collection;
        }
        return [];
    }

    public function getShipment()
    {
        if ($this->hasVariable('order')) {
            $collection = new Collection\Shipment();
            $collection->where(['order_id' => $this->getOrder()->getId()]);
            return $collection;
        }
        return [];
    }

    public function getCreditMemo()
    {
        if ($this->hasVariable('order')) {
            $collection = new Collection\CreditMemo();
            $collection->where(['order_id' => $this->getOrder()->getId()]);
            return $collection;
        }
        return [];
    }
}
