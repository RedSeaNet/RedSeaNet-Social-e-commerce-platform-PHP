<?php

namespace Redseanet\Checkout\Traits;

use Exception;
use Redseanet\Customer\Model\Address;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart;

trait Checkout {

    protected function validShippingAddress($data) {
        if (!isset($data['shipping_address_id'])) {
            throw new Exception('Please select shipping address');
        }
        $address = new Address();
        $address->load($data['shipping_address_id']);
        return $address;
    }

    protected function validBillingAddress($data) {
        if (!isset($data['billing_address_id'])) {
            return null;
        }
        $address = new Address();
        $address->load($data['billing_address_id']);
        return $address;
    }

    public function validPayment($data) {
        if (!isset($data['payment_method'])) {
            return null;
        }
        $className = $this->getContainer()->get('config')['payment/' . $data['payment_method'] . '/model'];
        $method = new $className();
        $result = $method->available($data);
        if ($result !== true) {
            return null;
        }
        return $method;
    }

    public function getShippingMethod($shipping_method) {
        if (isset($shipping_method)) {
            $className = $this->getContainer()->get('config')['shipping/' . $shipping_method . '/model'];
            return new $className();
        }
        return null;
    }

    public function validShipping($data, $cart = null) {
        if (!$cart) {
            $cart = Cart::instance();
        }
        $result = [];
        foreach ($cart->getItems() as $item) {
            if (!$item['is_virtual'] && $item['status'] && !isset($result[$item['store_id']])) {
                if (!isset($data['shipping_method'][$item['store_id']])) {
                    return null;
                }
                $className = $this->getContainer()->get('config')['shipping/' . preg_replace('/:[^:]+$/', '', $data['shipping_method'][$item['store_id']]) . '/model'];
                $result[$item['store_id']] = new $className();
                if (!$result[$item['store_id']]->available(['total' => $data['totals'][$item['store_id']]])) {
                    return null;
                }
            }
        }
        return $result;
    }

}
