<?php

namespace Redseanet\Admin\ViewModel\Sales\View;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Model\CreditMemo as Model;
use Redseanet\Customer\Model\Customer;
use Redseanet\Sales\Model\Order;
use Redseanet\Log\Model\Payment;

class CreditMemo extends Template
{
    protected $creditmemo = null;
    protected $order = null;
    protected $status = null;
    protected $phase = null;

    public function getCreditMemo()
    {
        if (is_null($this->creditmemo)) {
            $this->creditmemo = (new Model())->load($this->getQuery('id'));
        }
        return $this->creditmemo;
    }

    public function getOrder()
    {
        if (is_null($this->creditmemo)) {
            $creditMemo = $this->getCreditMemo();
        }
        if (is_null($this->order)) {
            $this->order = (new Order())->load($this->creditmemo['order_id']);
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
        $collection = $this->getCreditMemo()->getItems();
        return $collection;
    }

    public function getOrderModel()
    {
        error_reporting(E_ALL & ~E_NOTICE);
        $id = $this->getRequest()->getQuery('id');
        $invoice = (new Model())->load($id);
        $order = (new Order())->load($invoice['order_id']);
        return $order;
    }

    public function getOrderLogPayment()
    {
        $log = new Payment();
        $log->load($this->creditmemo['order_id'], 'order_id');
        return $log;
    }
}
