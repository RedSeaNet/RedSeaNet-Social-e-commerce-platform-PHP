<?php

namespace Redseanet\Checkout\Controller;

use Error;
use Exception;
use Redseanet\Customer\Model\Address;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart;
use Redseanet\Sales\Model\Order;

class OrderController extends ActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Lib\Traits\Rabbitmq;

    public function dispatch($request = null, $routeMatch = null)
    {
        $session = new Segment('customer');
        $config = $this->getContainer()->get('config');
        if (!$session->get('hasLoggedIn') && !$config['checkout/general/allow_guest']) {
            return $this->redirect('customer/account/login/?success_url=' . str_replace(['+', '/', '='], ['-', '_', ''], urlencode($this->getBaseUrl('checkout/order/'))));
        } elseif (Cart::instance()->offsetGet('base_total') < ($min = (float) $this->getContainer()->get('config')['checkout/sales/min_amount'])) {
            $currency = Cart::instance()->getCurrency();
            $this->addMessage($this->translate('The allowed minimal amount is %s, current is %s.', [$currency->convert($min, true), $currency->format(Cart::instance()->offsetGet('total'))]));
            return $this->redirect('checkout/cart/');
        }
        return parent::dispatch($request, $routeMatch);
    }

    public function indexAction()
    {
        $cart = Cart::instance();
        $items = $cart->getItems(true);
        $items->where(['status' => 1]);
        if (count($items) > 0) {
            foreach ($items as $item) {
                $options = json_decode($item['options'], true);
                foreach ($item['product']->getOptions() as $option) {
                    if ($option['is_required'] && !isset($options[$option->getId()])) {
                        return $this->redirectReferer('checkout/cart/');
                    }
                }
            }
            $root = $this->getLayout('checkout_order');
            $root->getChild('address', true)->setVariable('isVirtual', $cart->isVirtual());
            $root->getChild('payment', true)->setVariable('address', $cart->getShippingAddress())->setVariable('items', $items);
            $root->getChild('review', true)->setVariable('cart', $cart)->setVariable('items', $items);
            return $root;
        }
        return $this->redirectReferer('checkout/cart/');
    }

    public function shippingAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $store = $this->getRequest()->getQuery('store');
            $root = $this->getLayout('checkout_order_shipping');
            $cart = Cart::instance();
            $items = $cart->getItems();
            $itemStore = [];
            foreach ($items as $item) {
                $itemStore[$item['store_id']][] = $item;
            }
            $root->getChild('shipping', true)->setVariable('store_id', $store)->setVariable('isVirtual', $cart->isVirtual($store))->setVariable('current_shipping_method', json_decode($cart->offsetGet('shipping_method'), true))->setVariable('address', $cart->getShippingAddress())->setVariable('items', $itemStore[$store]);
            return ['store' => $store, 'html' => $root->__toString()];
        }
        return $this->notFoundAction();
    }

    public function paymentAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $root = $this->getLayout('checkout_order_payment');
            $store = $this->getRequest()->getQuery('store');
            $cart = Cart::instance();
            $items = $cart->getItems();
            $itemStore = [];
            if (empty($store)) {
                foreach ($items as $item) {
                    if ($item['status'] == 1) {
                        $itemStore[] = $item;
                    }
                }
            } else {
                foreach ($items as $item) {
                    if ($item['status'] == 1 && $item['store_id'] == $store) {
                        $itemStore[] = $item;
                    }
                }
            }
            $root->getChild('payment', true)->setVariable('store_id', $store)->setVariable('isVirtual', $cart->isVirtual($store))->setVariable('current_shipping_method', (!empty($cart->offsetGet('shipping_method')) ? json_decode($cart->offsetGet('shipping_method'), true) : ''))->setVariable('address', $cart->getShippingAddress())->setVariable('items', $itemStore);
            return $root;
        }
        return $this->notFoundAction();
    }

    public function reviewAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->getLayout('checkout_order_review');
        }
        return $this->notFoundAction();
    }

    public function couponAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $store = $this->getRequest()->getQuery('store');
            $root = $this->getLayout('checkout_order_coupon');
            $root->getChild('coupon', true)->setVariable('store', $store);
            return ['store' => $store, 'html' => $root->__toString()];
        }
        return $this->notFoundAction();
    }

    public function placeAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $cart = Cart::instance();
            if (!isset($data['csrf']) || !$this->validateCsrfKey($data['csrf'])) {
                $result['message'][] = ['message' => $this->translate('The form submitted did not originate from the expected site.'), 'level' => 'danger'];
                $result['error'] = 1;
            } elseif ($data['total'] != $cart['base_total']) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
            } else {
                try {
                    $config = $this->getContainer()->get('config');
                    $language_id = Bootstrap::getLanguage()->getId();
                    $this->beginTransaction();
                    $cartInfo = $cart->collateTotals()->toArray();
                    $isVirtual = $cart->isVirtual();
                    $items = $cart->abandon();
                    if (empty($items)) {
                        $this->rollback();
                        $this->flushRow($cart->getId(), null, 'sales_cart');
                        $this->flushList('sales_cart_item');
                        return $this->getRequest()->isXmlHttpRequest() ?
                                ['error' => 0, 'message' => [], 'redirect' => $this->getBaseUrl('checkout/cart/')] :
                                $this->redirect('checkout/cart/');
                    }
                    $totals = [];
                    foreach ($items as $item) {
                        if (!$item['is_virtual']) {
                            if (!isset($totals[$item['store_id']])) {
                                $totals[$item['store_id']] = 0;
                            }
                            $totals[$item['store_id']] += $item['base_total'] * $item['qty'];
                        }
                    }
                    $billingAddress = $this->validBillingAddress($data);
                    $paymentMethod = $this->validPayment(['total' => $cartInfo['base_total']] + $data);
                    if ($isVirtual) {
                        $cartInfo = [
                            'payment_method' => $data['payment_method'],
                            'customer_note' => isset($data['comment']) ? json_encode($data['comment']) : '{}'
                        ] + $cartInfo;
                        if ($billingAddress) {
                            $cartInfo = [
                                'billing_address_id' => $data['billing_address_id'],
                                'billing_address' => $billingAddress->display(false)
                            ] + $cartInfo;
                        }
                    } else {
                        $shippingAddress = $this->validShippingAddress($data);
                        $this->validShipping(['totals' => $totals] + $data);
                        $cartInfo = [
                            'shipping_address_id' => $data['shipping_address_id'],
                            'shipping_address' => isset($shippingAddress) ? $shippingAddress->display(false) : '',
                            'payment_method' => $data['payment_method'],
                            'shipping_method' => json_encode($data['shipping_method']),
                            'customer_note' => isset($data['comment']) ? json_encode($data['comment']) : '{}'
                        ] + $cartInfo;
                        $cartInfo = ($billingAddress ? [
                            'billing_address_id' => $data['billing_address_id'],
                            'billing_address' => $billingAddress->display(false)
                        ] : [
                            'billing_address_id' => $data['shipping_address_id'],
                            'billing_address' => $shippingAddress->display(false)
                        ]) + $cartInfo;
                    }
                    $orders = [];
                    if (isset($data['payment_data'])) {
                        $paymentMethod->saveData($cart, $data['payment_data']);
                    }
                    $itemsGroup = [];
                    $isVirtual = [];
                    foreach ($items as $item) {
                        $key = $item['warehouse_id'] . '-' . $item['store_id'];
                        if (!isset($itemsGroup[$key])) {
                            $itemsGroup[$key] = [];
                            $isVirtual[$key] = 1;
                        }
                        $itemsGroup[$key][] = $item;
                        $isVirtual[$key] &= (int) $item['is_virtual'];
                    }
                    foreach ($itemsGroup as $key => $items) {
                        $orders[$key] = (new Order())->place($key . '-' . $isVirtual[$key], $items, $cartInfo, $paymentMethod->getNewOrderStatus());
                        $data['increment_id'] = $orders[$key]['increment_id'];
                    }
                    $result['redirect'] = $paymentMethod->preparePayment($orders, $data);
                    if (isset($orders['openid'])) {
                        $result['prepay'] = (new Segment('payment'))->get('wechatpay');
                    }
                    $this->commit();

                    if (!empty($config['adapter']['mq'])) {
                        //mp
                        $this->getRabbitmqConnection();
                        $this->createRabbitmqChannel();
                        $this->declareRabbitmqQueue('customerlogin');
                        $this->declareRabbitmqExchange('customerlogin');
                        $data['language_id'] = $language_id;
                        $data['ids'] = [];
                        foreach ($orders as $order) {
                            $data['ids'][] = $order->getId();
                        }
                        $msgBody = ['eventName' => 'order.place.after.mq', 'data' => $data];
                        $this->sendPublishMqMessage(json_encode($msgBody));
                    }
                    $segment = new Segment('checkout');
                    $segment->set('hasNewOrder', 1);
                } catch (Error $e) {
                    $this->getContainer()->get('log')->logError($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                    $this->rollback();
                    $this->flushRow($cart->getId(), null, 'sales_cart');
                    $this->flushList('sales_cart_item');
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                    $this->rollback();
                    $this->flushRow($cart->getId(), null, 'sales_cart');
                    $this->flushList('sales_cart_item');
                }
            }
        }
        return $this->response($result, 'checkout/order/', 'checkout');
    }

    protected function validShippingAddress($data)
    {
        if (!isset($data['shipping_address_id'])) {
            throw new Exception('Please select shipping address');
        }
        $address = new Address();
        $address->load($data['shipping_address_id']);
        if ($address->offsetGet('customer_id')) {
            $segment = new Segment('customer');
            if (!$segment->get('hasLoggedIn') || $segment->get('customer')['id'] != $address->offsetGet('customer_id')) {
                throw new Exception('Invalid address ID');
            }
        }
        return $address;
    }

    protected function validBillingAddress($data)
    {
        if (!isset($data['billing_address_id'])) {
            return null;
        }
        $address = new Address();
        $address->load($data['billing_address_id']);
        if ($address->offsetGet('customer_id')) {
            $segment = new Segment('customer');
            if (!$segment->get('hasLoggedIn') || $segment->get('customer')['id'] != $address->offsetGet('customer_id')) {
                throw new Exception('Invalid address ID');
            }
        }
        return $address;
    }

    public function saveAddressAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $attribute = new Attribute();
            $attribute->withSet()
                    ->columns(['code'])
                    ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [])
                    ->where(['eav_entity_type.code' => Address::ENTITY_TYPE, 'is_required' => 1]);
            $required = [];
            $setId = $attribute[0]['attribute_set_id'];
            $attribute->walk(function ($item) use (&$required) {
                $required[] = $item['code'];
            });
            $result = $this->validateForm($data, $required);
            if ($result['error'] === 0) {
                $address = new Address();
                unset($data['customer_id'], $data['store_id'], $data['attribute_set_id']);

                try {
                    $segment = new Segment('customer');
                    $address->setData($data + [
                        'attribute_set_id' => $setId,
                        'store_id' => Bootstrap::getStore()->getId(),
                        'customer_id' => $segment->get('hasLoggedIn') ? $segment->get('customer')['id'] : null
                    ])->save();
                    if (!$segment->get('hasLoggedIn')) {
                        $ids = $segment->get('address') ?: [];
                        $ids[] = $address->getId();
                        $segment->set('address', $ids);
                    }
                    $result['data'] = ['id' => $address->getId(), 'content' => $address->display(), 'json' => json_encode($address->toArray())];
                    Cart::instance()->setData(
                        (isset($data['is_billing']) && $data['is_billing']) ? [
                            'billing_address_id' => $result['data']['id'],
                            'billing_address' => $result['data']['content']
                        ] : [
                            'shipping_address_id' => $result['data']['id'],
                            'shipping_address' => $result['data']['content'],
                            'billing_address_id' => $result['data']['id'],
                            'billing_address' => $result['data']['content']
                        ]
                    )->save();
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
        }

        return $this->response($result, 'checkout/order/', 'checkout');
    }

    public function defaultAddressAction()
    {
        $id = $this->getRequest()->getQuery('id');
        if ($id) {
            $address = new Address();
            $address->load($id)->setData('is_default', 1)->save();
        }
        //return $this->response(['error' => 0, 'message' => []], 'checkout/order/', 'checkout');
        return $this->redirectReferer();
    }

    public function deleteAddressAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                $address = new Address();
                try {
                    $address->load($data['id']);
                    if ($address->offsetGet('customer_id')) {
                        $segment = new Segment('customer');
                        if (!$segment->get('hasLoggedIn') || $segment->get('customer')['id'] != $address->offsetGet('customer_id')) {
                            throw new Exception('Invalid address ID');
                        }
                    } elseif ($data['id'] != Cart::instance()['shipping_address_id'] && $data['id'] != Cart::instance()['billing_address_id']) {
                        throw new Exception('Invalid address ID');
                    }
                    $address->remove();
                    $result['removeLine'] = 1;
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result, 'checkout/order/', 'checkout');
    }

    public function selectAddressAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (!isset($data['csrf']) || !$this->validateCsrfKey($data['csrf'])) {
                $result['message'][] = ['message' => $this->translate('The form submitted did not originate from the expected site.'), 'level' => 'danger'];
                $result['error'] = 1;
            } else {
                try {
                    $billingAddress = $this->validBillingAddress($data);
                    $cart = Cart::instance();
                    if ($cart->isVirtual()) {
                        if ($billingAddress) {
                            $cart->setData([
                                'billing_address_id' => $data['billing_address_id'],
                                'billing_address' => $billingAddress->display(false)
                            ])->collateTotals();
                        }
                    } else {
                        $shippingAddress = $this->validShippingAddress($data);
                        $cart->setData([
                            'shipping_address_id' => $data['shipping_address_id'],
                            'shipping_address' => $shippingAddress->display(false)
                        ])->setData($billingAddress ? [
                            'billing_address_id' => $data['billing_address_id'],
                            'billing_address' => $billingAddress->display(false)
                        ] : [
                            'billing_address_id' => $data['shipping_address_id'],
                            'billing_address' => $shippingAddress->display(false)
                        ])->collateTotals();
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result, 'checkout/order/', 'checkout');
    }

    public function validPayment($data)
    {
        if (!isset($data['payment_method'])) {
            throw new Exception('Please select payment method');
        }
        $className = $this->getContainer()->get('config')['payment/' . $data['payment_method'] . '/model'];
        $method = new $className();
        $result = $method->available($data);
        if ($result !== true) {
            throw new Exception(is_string($result) ? $result : 'Invalid payment method');
        }
        return $method;
    }

    public function selectPaymentAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (!isset($data['csrf']) || !$this->validateCsrfKey($data['csrf'])) {
                $result['message'][] = ['message' => $this->translate('The form submitted did not originate from the expected site.'), 'level' => 'danger'];
                $result['error'] = 1;
            } else {
                try {
                    $this->validPayment(['total' => Cart::instance()['base_total']] + $data);
                    $cart = Cart::instance();
                    $cart->setData([
                        'payment_method' => $data['payment_method']
                    ])->collateTotals();
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result, 'checkout/order/', 'checkout');
    }

    public function validShipping($data)
    {
        $cart = Cart::instance();
        $result = [];
        foreach ($cart->getItems() as $item) {
            if (!$item['is_virtual'] && $item['status'] && !isset($result[$item['store_id']])) {
                if (!isset($data['shipping_method'][$item['store_id']])) {
                    throw new Exception('Please select shipping method');
                }
                $className = $this->getContainer()->get('config')['shipping/' . preg_replace('/:[^:]+$/', '', $data['shipping_method'][$item['store_id']]) . '/model'];
                $result[$item['store_id']] = new $className();
                if (!$result[$item['store_id']]->available(['total' => $data['totals'][$item['store_id']]])) {
                    throw new Exception('Invalid shipping method');
                }
            }
        }
        return $result;
    }

    public function selectShippingAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (!isset($data['csrf']) || !$this->validateCsrfKey($data['csrf'])) {
                $result['message'][] = ['message' => $this->translate('The form submitted did not originate from the expected site.'), 'level' => 'danger'];
                $result['error'] = 1;
            } else {
                try {
                    $cart = Cart::instance();
                    if (!$cart->isVirtual()) {
                        $totals = [];
                        foreach ($cart->getItems() as $item) {
                            if (!$item['is_virtual']) {
                                if (!isset($totals[$item['store_id']])) {
                                    $totals[$item['store_id']] = 0;
                                }
                                $totals[$item['store_id']] += $item['base_total'] * $item['qty'];
                            }
                        }
                        $this->validShipping(['totals' => $totals] + $data);
                        $cart->setData([
                            'shipping_method' => json_encode($data['shipping_method'])
                        ])->collateTotals();
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result, 'checkout/order/', 'checkout');
    }

    public function selectCouponAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (!isset($data['csrf']) || !$this->validateCsrfKey($data['csrf'])) {
                $result['message'][] = ['message' => $this->translate('The form submitted did not originate from the expected site.'), 'level' => 'danger'];
                $result['error'] = 1;
            } else {
                try {
                    $cart = Cart::instance();
                    $this->getContainer()->get('eventDispatcher')->trigger('promotion.apply', ['model' => $cart]);
                    $cart->setData([
                        'coupon' => json_encode($data['coupon'])
                    ])->collateTotals();
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result, 'checkout/order/', 'checkout');
    }
}
