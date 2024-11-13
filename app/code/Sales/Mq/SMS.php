<?php

namespace Redseanet\Sales\Mq;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Listeners\MqInterface;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Source\Refund\Service;
use Redseanet\Sales\Source\Refund\Status;

class SMS implements MqInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\Translate;

    private $sender;

    private function getSender($class)
    {
        if (is_null($this->sender)) {
            $this->sender = new $class();
        }
        return $this->sender;
    }

    private function send($template, $to, $params = [])
    {
        $config = $this->getContainer()->get('config');
        if ($service = $config['message/general/service']) {
            $this->getSender($config['message/' . $service . '/model'])
                    ->send($to, $config[$template], $params);
        }
    }

    private function getCustomer(Order $order)
    {
        $customer = $order->getCustomer();
        if ($customer && !empty($customer['cel'])) {
            return $customer;
        } else {
            $address = $order->getShippingAddress() ?: $order->getBillingAddress();
            if (!$address['tel']) {
                return null;
            }
            return [
                'username' => $address['name'],
                'cel' => $address['tel']
            ];
        }
    }

    public function afterOrderPlaced($e)
    {
        $model = $e['model'];
        $customer = $this->getCustomer($model);
        if ($customer) {
            $items = $model->getItems(true);
            $qty = 0;
            foreach ($items as $item) {
                $qty += (float) $item['qty'];
            }
            $this->send('checkout/message/order_placed_template', $customer['cel'], [
                'username' => $customer['username'],
                'id' => $model->offsetGet('increment_id'),
                'products' => $items[0]['product']['name'],
                'qty' => $qty,
                'billing_address' => $model['billing_address'],
                'shipping_address' => $model['shipping_address'],
                'subtotal' => $model['subtotal'],
                'tax' => $model['tax'],
                'discount' => $model['discount'],
                'shipping' => $model['shipping'],
                'total' => $model['total'],
                'created_at' => date('Y-m-d H:i'),
                'order' => ['model' => $model]
            ]);
        }
    }

    public function afterInvoiceSaved($e)
    {
        if ($e['isNew']) {
            $model = $e['model'];
            $items = $model->getItems(true);
            if (!$model instanceof Order) {
                $model = $model->getOrder();
            }
            $customer = $this->getCustomer($model);
            if ($customer) {
                $qty = 0;
                foreach ($items as $item) {
                    $qty += (float) $item['qty'];
                }
                $this->send('checkout/message/invoice_template', $customer['cel'], [
                    'username' => $customer['username'],
                    'id' => $model->offsetGet('increment_id'),
                    'status' => $this->translate($model->getStatus()->offsetGet('name'), [], 'sales', @$model->getLanguage()['code']),
                    'products' => $items[0]['product']['name'],
                    'qty' => $qty,
                    'subtotal' => $model['subtotal'],
                    'tax' => $model['tax'],
                    'discount' => $model['discount'],
                    'shipping' => $model['shipping'],
                    'total' => $model['total'],
                    'created_at' => $model['created_at'],
                    'updated_at' => $model['updated_at'],
                    'order' => ['model' => $model]
                ]);
            }
        }
    }

    public function afterShipmentSaved($e)
    {
        if ($e['isNew']) {
            $model = $e['model'];
            $track = $e['track'] ?? [];
            $items = $model->getItems(true);
            if (!$model instanceof Order) {
                $model = $model->getOrder();
            }
            $customer = $this->getCustomer($model);
            if ($customer) {
                $qty = 0;
                foreach ($items as $item) {
                    $qty += (float) $item['qty'];
                }
                $this->send('checkout/message/shipment_template', $customer['cel'], [
                    'username' => $customer['username'],
                    'name' => $model->getShippingAddress()['name'],
                    'carrier' => $track['carrier'] ?? '',
                    'tracking_number' => $track['tracking_number'] ?? '',
                    'id' => $model->offsetGet('increment_id'),
                    'status' => $this->translate($model->getStatus()->offsetGet('name'), [], 'sales', @$model->getLanguage()['code']),
                    'products' => $items[0]['product']['name'],
                    'qty' => $qty,
                    'subtotal' => $model['subtotal'],
                    'tax' => $model['tax'],
                    'discount' => $model['discount'],
                    'shipping' => $model['shipping'],
                    'total' => $model['total'],
                    'created_at' => $model['created_at'],
                    'updated_at' => $model['updated_at']
                ]);
            }
        }
    }

    public function orderStatusChanged($e)
    {
        if ($e['is_customer_notified']) {
            $model = $e['model'];
            if (!$model instanceof Order) {
                $model = $model->getOrder();
            }
            $items = $model->getItems(true);
            $qty = 0;
            foreach ($items as $item) {
                $qty += (float) $item['qty'];
            }
            $customer = $this->getCustomer($model);
            $this->send('checkout/message/shipment_template', $customer['cel'], [
                'username' => $customer['username'],
                'id' => $model->offsetGet('increment_id'),
                'status' => $this->translate($model->getStatus()->offsetGet('name'), [], 'sales', @$model->getLanguage()['code']),
                'products' => $items[0]['product']['name'],
                'qty' => $qty,
                'subtotal' => $model['subtotal'],
                'tax' => $model['tax'],
                'discount' => $model['discount'],
                'shipping' => $model['shipping'],
                'total' => $model['total'],
                'created_at' => $model['created_at'],
                'updated_at' => $model['updated_at'],
                'order' => ['model' => $model]
            ]);
        }
    }

    public function rma($e)
    {
        $model = $e['model'];
        $order = $model->getOrder();
        $customer = $this->getCustomer($order);
        $items = $model->getItems(true);
        $qty = 0;
        foreach ($items as $item) {
            $qty += (float) $item['qty'];
        }
        $params = [
            'username' => $customer['username'],
            'id' => $order->offsetGet('increment_id'),
            'products' => $items[0]['product']['name'],
            'qty' => $qty,
            'service' => $this->translate((new Service())->getSourceArray()[$model['service']], [], 'sales', @$order->getLanguage()['code']),
            'status' => $this->translate((new Status())->getSourceArray($model['service'])[$model['status']], [], 'sales', @$order->getLanguage()['code']),
            'placed_at' => $order['created_at'],
            'created_at' => $model['created_at'],
            'updated_at' => $model['updated_at'] ?: $model['created_at'],
            'rma' => ['model' => $model]
        ];
        $this->send('checkout/message/rma_template', $customer['cel'], $params);
        if (class_exists('\\Redseanet\\Retailer\\Model\\Retailer')) {
            $retailer = new Retailer();
            $retailer->load($order['store_id'], 'store_id');
            if ($retailer->getId()) {
                $customer = new Customer();
                $customer->load($retailer['customer_id']);
                $this->send('checkout/message/rma_template', $customer['cel'], $params);
            }
        }
    }
}
