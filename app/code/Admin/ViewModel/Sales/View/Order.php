<?php

namespace Redseanet\Admin\ViewModel\Sales\View;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Model\Order as Model;
use Redseanet\Log\Model\Payment;

class Order extends Template
{
    protected $order = null;
    protected $status = null;
    protected $phase = null;

    public function getOrder()
    {
        if (is_null($this->order)) {
            $this->order = (new Model())->load($this->getQuery('id'));
        }
        return $this->order;
    }

    public function getCustomer()
    {
        if ($id = $this->getOrder()->offsetGet('customer_id')) {
            $customer = new Customer();
            $customer->load($id);
            return $customer;
        }
        return null;
    }

    public function getCollection()
    {
        $collection = $this->getOrder()->getItems();
        return $collection;
    }

    public function getStatus()
    {
        if (is_null($this->status)) {
            $this->status = $this->getOrder()->getStatus();
        }
        return $this->status;
    }

    public function getPhase()
    {
        if (is_null($this->phase)) {
            $this->phase = $this->getStatus()->getPhase();
        }
        return $this->phase;
    }

    public function getOrderModel()
    {
        $id = $this->getRequest()->getQuery('id');
        $order = (new Model())->load($id);
        return $order;
    }

    public function getOrderLogPayment()
    {
        $log = new Payment();
        $id = $this->getRequest()->getQuery('id');
        $log->load($id, 'order_id');
        return $log;
    }
}
