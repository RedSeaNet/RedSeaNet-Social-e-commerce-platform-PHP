<?php

namespace Redseanet\Retailer\ViewModel\Sales\View;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Model\Shipment as Model;
use Redseanet\Sales\Model\Order as OrderModel;
use Redseanet\Customer\Model\Customer;
use TCPDFBarcode;
use Pelago\Emogrifier;

class Shipment extends Template
{
    protected $shipment = null;
    protected $order = null;
    protected $status = null;
    protected $phase = null;

    public function getShipment()
    {
        if (is_null($this->shipment)) {
            $this->shipment = (new Model())->load($this->getQuery('id'));
        }
        return $this->shipment;
    }

    public function getOrder()
    {
        if (is_null($this->order)) {
            $this->order = (new OrderModel())->load($this->shipment['order_id']);
        }
        return $this->order;
    }

    public function getCustomer()
    {
        if ($id = $this->getShipment()->offsetGet('customer_id')) {
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

    public function getOrderModel()
    {
        error_reporting(E_ALL & ~E_NOTICE);
        $id = $this->getRequest()->getQuery('id');
        $invoice = (new Model())->load($id);
        $order = (new OrderModel())->load($invoice['order_id']);
        return $order;
    }
}
