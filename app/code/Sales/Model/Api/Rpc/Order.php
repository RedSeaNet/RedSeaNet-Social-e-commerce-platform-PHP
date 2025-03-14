<?php

namespace Redseanet\Sales\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Sales\Model\Collection\Cart as collectionCart;
use Redseanet\Sales\Model\Cart;
use Redseanet\Sales\Model\Collection\Cart\Item as ItemCollection;
use Laminas\Db\Sql\Where;
use Redseanet\Lib\Bootstrap;
use Redseanet\Resource\Model\Resource;
use Redseanet\Lib\Db\YsInsert;
use Redseanet\Lib\Session\Segment;
use Redseanet\Catalog\Model\Product;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Model\Merchant;
use Redseanet\Lib\Model\Collection\Merchant as merchantCollection;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Model\Collection\Store as storeCollection;
use Redseanet\Sales\Source\ShippingMethod;
use Redseanet\Sales\Source\PaymentMethod;
use Redseanet\Customer\Model\Address;
use Redseanet\Sales\Model\Order as Model;
use Redseanet\Sales\Model\Order\Item as orderItem;
use Redseanet\Sales\Model\Collection\Order as Collection;
use Redseanet\I18n\Model\Locate;
use Redseanet\Payment\Model\AbstractMethod as paymentAbstractMethod;
use Redseanet\Shipping\Model\AbstractMethod as shippingAbstractMethod;
use Laminas\Db\Sql\Predicate\In;
use Redseanet\Promotion\Model\Collection\Rule;
use Redseanet\Lib\Model\Language;
use Redseanet\I18n\Model\Currency;

class Order extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;
    use \Redseanet\Lib\Traits\Translate;
    use \Redseanet\Checkout\Traits\Checkout;

    /**
     * @param int $customerId
     * @return Model
     */
    protected function getCart($customerId) {
        $segment = new Segment('customer');
        $segment->set('hasLoggedIn', true)
                ->set('customer', (new Customer())->setId($customerId));
        return Cart::instance();
    }

    /**
     * @param int $id api user id
     * @param string $token
     * @param int $customerId
     * @param array $chosenItems
     * @return array
     */
    public function cartInfoToConfirmOrder($id, $token, $customerId, $chosenItems = [], $language = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            $config = $this->getContainer()->get('config');
            $cart = $this->getCart($customerId);
            $resultData = $cart->toArray();
            if ($resultData['additional'] != '') {
                $additional = json_decode($resultData['additional'], true);
                unset($resultData['additional']);
                $resultData['additional'] = $additional;
            }
            if ($resultData['discount_detail'] != '') {
                $discountDetail = json_decode($resultData['discount_detail'], true);
                unset($resultData['discount_detail']);
                $resultData['discount_detail'] = $discountDetail;
            }
            $resultData['shipping_address_data'] = [];
            if (!empty($resultData['shipping_address_id'])) {
                $shippingData = new Address();
                $shippingData->load($resultData['shipping_address_id']);
                $resultData['shipping_address_data'] = $shippingData->toArray();
            }
            $storeData = [];
            $items = new ItemCollection();
            $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
            $items->where(['cart_id' => $cart->getId()]);
            $items->load(true, true);
            $resultData['items'] = [];
            $storeIds = [];
            $storeItems = [];
            $resultData['unit_total'] = 0;
            foreach ($items as $item) {
                if (isset($item['image']) && $item['image'] != '') {
                    $image = $this->getResourceUrl('image/' . $item['image']);
                } else {
                    $image = $this->getPubUrl('frontend/images/placeholder.png');
                }
                unset($item['image']);
                $item['image'] = $image;
                $storeItems[$item['store_id']][] = $item;
                $storeIds[] = $item['store_id'];
                if (isset($storeData[$item['store_id']]['total'])) {
                    $storeData[$item['store_id']]['total'] = $storeData[$item['store_id']]['total'] + $item['base_total'];
                } else {
                    $storeData[$item['store_id']]['total'] = $item['base_total'];
                }
                if (isset($storeData[$item['store_id']]['unit_total'])) {
                    $storeData[$item['store_id']]['unit_total'] = $storeData[$item['store_id']]['unit_total'] + intval($item['qty']);
                } else {
                    $storeData[$item['store_id']]['unit_total'] = intval($item['qty']);
                }
                $resultData['unit_total'] = $resultData['unit_total'] + intval($item['qty']);
            }
            array_unique($storeIds);
            $storesInfo = new storeCollection();
            $storesInfo->where(new In('id', $storeIds));
            $storesInfo->load(true, true);
            $address = $cart->getShippingAddress();
            $countryCode = $address ? (new Locate())->getCode('country', $address->offsetGet('country')) : '';
            for ($s = 0; $s < count($storesInfo); $s++) {
                $storeData[$storesInfo[$s]['id']]['id'] = $storesInfo[$s]['id'];
                $storeData[$storesInfo[$s]['id']]['name'] = $storesInfo[$s]['name'];
                $storeData[$storesInfo[$s]['id']]['items'] = $storeItems[$storesInfo[$s]['id']];
                $storeData[$storesInfo[$s]['id']]['shipping_method'] = [];
                if ($cart->isVirtual($storesInfo[$s]['id'])) {
                    $storeData[$storesInfo[$s]['id']]['isvirtal'] = true;
                } else {
                    $storeData[$storesInfo[$s]['id']]['isvirtal'] = false;
                    foreach ($config['system']['shipping']['children'] as $code => $info) {
                        $className = $config['shipping/' . $code . '/model'];
                        $country = $config['shipping/' . $code . '/country'];
                        $model = new $className();
                        if ($model instanceof shippingAbstractMethod && $model->available(['total' => $storeData[$storesInfo[$s]['id']]['total']]) &&
                                (!$countryCode || !$country || in_array($countryCode, explode(',', $country)))) {
                            $storeData[$storesInfo[$s]['id']]['shipping_method'][] = ['code' => $code, 'label' => $config['shipping/' . $code . '/label']];
                        }
                    }
                }
            }
            $resultData['stores'] = [];
            foreach ($storeData as $key => $storeValue) {
                $resultData['stores'][] = $storeValue + ['store_id' => $key];
            }
            $resultData['payment_method'] = [];
            if ($total = (float) $resultData['base_total']) {
                foreach ($config['system']['payment']['children'] as $code => $info) {
                    if ($code === 'payment_free') {
                        continue;
                    }
                    $className = $config['payment/' . $code . '/model'];
                    $country = $config['payment/' . $code . '/country'];
                    $model = new $className();
                    if ($model instanceof paymentAbstractMethod && $model->available(['total' => $total]) === true &&
                            (!$countryCode || !$country || in_array($countryCode, explode(',', $country)))) {
                        $resultData['payment_method'][] = ['code' => $code, 'label' => $config['payment/' . $code . '/label']];
                    }
                }
            } else {
                $resultData['payment_method'][] = ['code' => 'payment_free', 'label' => $config['payment/payment_free/label']];
            }
            $resultData['shipping_method'] = json_decode($resultData['shipping_method'], true);
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get cart information successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => $resultData, 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param string $merchantId
     * @return array
     */
    public function getMerchant($id, $token, $merchantId = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($merchantId != '') {
            $merchant = new Merchant();
            $merchant->load(intval($merchantId));
            $this->responseData = ['statusCode' => '200', 'data' => $merchant->toArray(), 'message' => 'get merchant infomation successfully'];
            return $this->responseData;
        } else {
            $merchant = new merchantCollection();
            $merchant->load(true);
            $this->responseData = ['statusCode' => '200', 'data' => $merchant->toArray(), 'message' => 'get merchant infomation successfully'];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param string $storeIds
     * @return array
     */
    public function getStore($id, $token, $storeIds = []) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (is_array($storeIds) && count($storeIds) > 0) {
            $store = new storeCollection();
            $store->where(new In('id', $storeIds));
            $store->load(true, true);
            $returnData = $store->toArray();
            $this->responseData = ['statusCode' => '200', 'data' => $returnData, 'message' => 'get store infomation successfully'];
            return $this->responseData;
        } else {
            $store = new storeCollection();
            $store->load(true, true);
            $returnData = $store->toArray();
            $this->responseData = ['statusCode' => '200', 'data' => $returnData, 'message' => 'get store infomation successfully'];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param array $storeIds
     * @return array
     */
    public function getShippingMethod($id, $token, $customerId, $storeIds = [], $shipping_address_id = 0, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $language = new Language();
        $language->load($languageId);
        $cart = $this->getCart($customerId);
        $resultData = [];

        $address = $cart->getShippingAddress();
        $countryCode = $address ? (new Locate())->getCode('country', $address->offsetGet('country')) : '';
        $items = [];
        if (is_array($storeIds) && count($storeIds) > 0) {
            for ($i = 0; $i < count($storeIds); $i++) {
                $resultData[$storeIds[$i]] = [];
                $resultData[$storeIds[$i]]['shippingmethod'] = [];
                if ($cart->isVirtual($storeIds[$i])) {
                    $resultData[$storeIds[$i]]['isvirtal'] = true;
                } else {
                    $resultData[$storeIds[$i]]['isvirtal'] = false;
                    $items = $cart->getItems();
                    $total = 0;
                    foreach ($items as $item) {
                        if ($item['store_id'] == $storeIds[$i]) {
                            $total += $item['base_total'];
                        }
                    }
                    foreach ($config['system']['shipping']['children'] as $code => $info) {
                        $className = $config['shipping/' . $code . '/model'];
                        $country = $config['shipping/' . $code . '/country'];
                        $model = new $className();
                        if ($model instanceof shippingAbstractMethod && $model->available(['total' => $total]) &&
                                (!$countryCode || !$country || in_array($countryCode, explode(',', $country)))) {
                            $resultData[$storeIds[$i]]['shippingmethod'][] = ['code' => $code, 'label' => $this->translate($config['shipping/' . $code . '/label'], [], null, $language['code']), 'fee' => $model->getShippingRate($items)];
                        }
                    }
                }
            }
        }
        $resultData[0]['method'] = [];
        $resultData[0]['isvirtal'] = false;
        $total = $cart->offsetGet('base_total');
        foreach ($config['system']['shipping']['children'] as $code => $info) {
            $className = $config['shipping/' . $code . '/model'];
            $country = $config['shipping/' . $code . '/country'];
            $model = new $className();
            if ($model instanceof shippingAbstractMethod && $model->available(['total' => $total]) &&
                    (!$countryCode || !$country || in_array($countryCode, explode(',', $country)))) {
                $resultData[0]['shippingmethod'][] = ['code' => $code, 'label' => $this->translate($config['shipping/' . $code . '/label'], [], null, $language['code']), 'fee' => $model->getShippingRate($items)];
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get shipping method successfully'];
        return $this->responseData;
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @return array
     */
    public function getPaymentMethod($id, $token, $customerId, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        $language = new Language();
        $language->load($languageId);
        $cart = $this->getCart($customerId);
        $resultData = [];
        $address = $cart->getShippingAddress();
        if ($total = (float) $cart->offsetGet('base_total')) {
            $countryCode = $address ? (new Locate())->getCode('country', $address->offsetGet('country')) : '';
            foreach ($config['system']['payment']['children'] as $code => $info) {
                if ($code === 'payment_free') {
                    continue;
                }
                $className = $config['payment/' . $code . '/model'];
                $country = $config['payment/' . $code . '/country'];
                $model = new $className();
                if ($model instanceof paymentAbstractMethod && $model->available(['total' => $total]) === true &&
                        (!$countryCode || !$country || in_array($countryCode, explode(',', $country)))) {
                    $resultData[$code] = $this->translate($config['payment/' . $code . '/label'], [], null, $language['code']);
                }
            }
        } else {
            $resultData['payment_free'] = $config['payment/payment_free/label'];
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get payment method successfully'];
        return $this->responseData;
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param array $data
     * @return array
     */
    public function placeOrder($id, $token, $customerId, $data, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($data)));

        $cart = $this->getCart($customerId);
        $this->beginTransaction();
        $cartInfo = $cart->collateTotals()->toArray();
        $isVirtual = $cart->isVirtual();
        $items = $cart->abandon();
        if (empty($items)) {
            $this->rollback();
            $this->flushRow($cart->getId(), null, 'sales_cart');
            $this->flushList('sales_cart_item');
            $this->responseData = ['statusCode' => '404', 'data' => [], 'message' => 'your shopping cart is empty'];
            return $this->responseData;
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
        if ($this->responseData['statusCode'] != 200) {
            return $this->responseData;
        }
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
            if ($this->responseData['statusCode'] != 200) {
                return $this->responseData;
            }

            $this->validShipping(['totals' => $totals] + $data, $customerId);
            if ($this->responseData['statusCode'] != 200) {
                return $this->responseData;
            }
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
            $orders[$key] = (new Model())->place($key . '-' . $isVirtual[$key], $items, $cartInfo, $paymentMethod->getNewOrderStatus());
        }
        $cart->collateTotals();
        $this->commit();
        foreach ($orders as $orderM) {
            $this->getContainer()->get('eventDispatcher')->trigger('order.place.after', ['model' => $orderM]);
        }
        $this->flushList('sales_cart');
        $this->flushList('sales_cart_item');
        foreach ($orders as $key => $order) {
            $orders[$key] = $order->toArray();
        }
        $this->responseData = ['statusCode' => '200', 'data' => $orders, 'message' => 'place orders successfully'];
        return $this->responseData;
    }

    protected function validBillingAddress($data) {
        if (!isset($data['billing_address_id'])) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'billing_address_id can not bet null'];
            return $this->responseData;
        }
        $address = new Address();
        $address->load($data['billing_address_id']);
        if ($address->offsetGet('customer_id')) {
            $segment = new Segment('customer');
            if (!$segment->get('hasLoggedIn') || $segment->get('customer')['id'] != $address->offsetGet('customer_id')) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Invalid address ID'];
                return $this->responseData;
            }
        }
        return $address;
    }

    protected function validShippingAddress($data) {
        if (!isset($data['shipping_address_id'])) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Please select shipping address'];
            return $this->responseData;
        }
        $address = new Address();
        $address->load($data['shipping_address_id']);
        if ($address->offsetGet('customer_id')) {
            $segment = new Segment('customer');
            if (!$segment->get('hasLoggedIn') || $segment->get('customer')['id'] != $address->offsetGet('customer_id')) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Invalid address ID'];
                return $this->responseData;
            }
        }
        return $address;
    }

    public function validShipping($data, $customerId) {
        $cart = $this->getCart($customerId);
        $result = [];
        foreach ($cart->getItems() as $item) {
            if (!$item['is_virtual'] && $item['status'] && !isset($result[$item['store_id']])) {
                if (!isset($data['shipping_method'][$item['store_id']])) {
                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Please select shipping method'];
                    return $this->responseData;
                }
                $className = $this->getContainer()->get('config')['shipping/' . preg_replace('/:[^:]+$/', '', $data['shipping_method'][$item['store_id']]) . '/model'];

                $result[$item['store_id']] = new $className();
                $method = $result[$item['store_id']]->available(['total' => $data['totals'][$item['store_id']]]);

                if (!$method) {
                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Invalid shipping method'];
                    return $this->responseData;
                }
            }
        }
        return $result;
    }

    public function validPayment($data) {
        if (!isset($data['payment_method'])) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Please select payment method'];
            return $this->responseData;
        }
        $className = $this->getContainer()->get('config')['payment/' . $data['payment_method'] . '/model'];
        $method = new $className();
        $result = $method->available($data);
        if ($result !== true) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => is_string($result) ? $result : 'Invalid payment method'];
            return $this->responseData;
        }
        return $method;
    }

    /**
     * @param string $id
     * @param string $token
     * @param array $conditions
     * @return array
     */
    public function getOrder($id, $token, $conditions, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($conditions)));
        $collection = new Collection();
        if (empty($conditions["asc"]) && empty($conditions["desc"])) {
            $conditions["desc"] = "created_at";
        }
        $this->filter($collection, $conditions);
        $resultData = [];
        $itemColumns = [];
        //echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $collection->walk(function ($item) use (&$resultData, $itemColumns, $languageId) {
            $items = $item->getItems(true);
            $items->load(true, true);
            $tmpItems = [];
            for ($i = 0; $i < count($items); $i++) {
                $product = new Product();
                $product->load($items[$i]['product_id']);
                $options = json_decode($items[$i]['options'], true);
                $image = $this->getPubUrl('frontend/images/placeholder.png');
                //$product->getThumbnail($options);
                if (!empty($items["image"])) {
                    $image = $this->getResourceUrl("image/" . $items["image"]);
                }
                unset($items[$i]['options']);
                $items[$i]["image"] = $image;
                $items[$i]["options"] = $options;
                $tmpItems[] = $items[$i];
            }
            $item['status'] = ($item->getStatus())->toArray();
            $item['store'] = ($item->getStore())->toArray();
            $currency = new Currency();
            $currency->load($item['currency'], 'code');
            $resultData[] = $item->toArray() + ['items' => $tmpItems, 'currencyData' => $currency->toArray()];
        });
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get order list successfully'];
        return $this->responseData;
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $orderId
     * @return array
     */
    public function getOrderById($id, $token, $orderId, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($conditions)));
        $language = new Language();
        $language->load($languageId);

        $order = new Model();
        $order->load($orderId);
        $items = $order->getItems(true);
        $items->load(true, true);
        $tmpItems = [];
        for ($i = 0; $i < count($items); $i++) {
            $product = new Product();
            $product->load($items[$i]['product_id']);
            $options = json_decode($items[$i]['options'], true);
            $image = $product->getThumbnail($options);
            unset($items[$i]['options']);
            $tmpItems[] = $items[$i] + ['image' => $image, 'options' => $options];
        }
        $store = $order->getStore();
        $status = $order->getStatus();
        $currency = new Currency();
        $currency->load($order['currency'], 'code');
        $payment_method_label = $this->translate(($order->getPaymentMethod())->getLabel(), [], null, $language['code']);
        $shipping_menthod_label = $this->translate(($order->getShippingMethod())->getLabel(), [], null, $language['code']);
        $resultData = $order->toArray() + ['items' => $tmpItems, 'store' => ($store != null ? $store->toArray() : []), 'status' => ($status != null ? $status->toArray() : []), 'payment_method_label' => $payment_method_label, 'shipping_menthod_label' => $shipping_menthod_label, 'currencyData' => $currency->toArray()];
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get order list successfully'];
        return $this->responseData;
    }

    public function getCoupons($id, $token, $customerId, $conditionData = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $resultData = [];
        $collection = new Rule();
        $collection->withStore(true)
                ->where(['promotion.use_coupon' => 1]);
        //echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());

        if (!isset($conditionData['limit']) || $conditionData['limit'] == '') {
            $conditionData['limit'] = 20;
        } else {
            $conditionData['limit'] = intval($conditionData['limit']);
        }
        if (!isset($conditionData['page']) || $conditionData['page'] == '') {
            $conditionData['page'] = 1;
        } else {
            $conditionData['page'] = intval($conditionData['page']);
        }
        $total = $collection->count();
        $last_page = ceil($total / $conditionData['limit']);
        $resultData['pagination'] = [
            "total" => $total,
            "per_page" => $conditionData['limit'],
            "current_page" => $conditionData['page'],
            "last_page" => $last_page,
            "next_page" => ($last_page > $conditionData['page'] ? $conditionData['page'] + 1 : $last_page),
            "previous_page" => ($conditionData['page'] > 1 ? $conditionData['page'] - 1 : 1),
            "has_next_page" => ($last_page > $conditionData['page'] ? true : false),
            "has_previous_page" => ($conditionData['page'] > 1 && $last_page > 1 ? true : false)
        ];
        if ($conditionData['page'] > 1) {
            $collection->order('id DESC')->limit($conditionData['limit'])->offset((int) ($conditionData['page'] - 1) * $conditionData['limit']);
        } else {
            $collection->order('id DESC')->limit($conditionData['limit'])->offset(0);
        }
        $resultData["coupons"] = [];
        foreach ($collection as $rule) {
            if ($this->matchCoupons($rule->getCondition(), true, $customerId)) {
                $_coupon = $rule->toArray();
                if (!empty($_coupon["store_id"])) {
                    $stores = new storeCollection;
                    $stores->columns(["id", "code", "name"]);
                    $stores->where("id in (" . implode(",", $_coupon["store_id"]) . ") and status=1");
                    $_coupon["stores"] = [];
                    if (count($stores) > 0) {
                        foreach ($stores as $store) {
                            $_coupon["stores"][] = $store->toArray();
                        }
                    }
                } else {
                    $_coupon["stores"] = [];
                }
                $resultData["coupons"][] = $_coupon;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get coupon list successfully'];
        return $this->responseData;
    }

    public function matchCoupons($condition, $default, $customerId) {
        if ($condition['identifier'] === 'customer_id') {
            return $condition['operator'] === '=' ? $customerId == $condition['value'] : $customerId != $condition['value'];
        } elseif ($condition['identifier'] === 'customer_group') {
            $customer = new Customer();
            $customer->load($customerId);
            foreach ($customer->getGroup() as $group) {
                if ($condition['operator'] === '=' && $group['id'] == $condition['value'] || $condition['operator'] !== '=' && $group['id'] != $condition['value']) {
                    return true;
                }
            }
            return false;
        } elseif ($condition['identifier'] === 'customer_level') {
            $customer = new Customer();
            $customer->load($customerId);
            return $condition['operator'] === '=' ? $customer->getLevel() == $condition['value'] : $customer->getLevel() != $condition['value'];
        } elseif ($condition['identifier'] === 'combination') {
            $result = $condition['operator'] === 'and' ? 1 : 0;
            foreach ($condition->getChildren() as $child) {
                if ($condition['operator'] === 'and') {
                    $result &= (int) $this->matchCoupons($child, $condition['value'], $customerId);
                    if (!$result) {
                        break;
                    }
                } else {
                    $result |= (int) $this->matchCoupons($child, $condition['value'], $customerId);
                    if ($result) {
                        break;
                    }
                }
            }
            return $result === (int) $condition['value'];
        } else {
            return $default;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param array $items
     * @param int $shipping_address_id
     * @param int $languageId
     * @param string $currencyCode
     * @return array
     */
    public function getShippingMethodByItems($id, $token, $items = [], $shipping_address_id = 0, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $language = new Language();
        $language->load($languageId);
        $address = (new Address())->load($shipping_address_id);
        $shippingMethods = (new ShippingMethod())->getSourceArray($address, $items);
        $this->responseData = ['statusCode' => '200', 'data' => $shippingMethods, 'message' => 'get shipping method successfully'];
        return $this->responseData;
    }

    /**
     * @param string $id
     * @param string $token
     * @param array $codition
     * @return array
     */
    public function getPaymentMethodByCondition($id, $token, $codition = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        $language = new Language();
        $language->load($languageId);
        $resultData = [];
        $address = (new Address())->load($codition['shipping_address_id']);

        $countryCode = $address ? (new Locate())->getCode('country', $address->offsetGet('country')) : '';
        foreach ($config['system']['payment']['children'] as $code => $info) {
            if ($code === 'payment_free') {
                continue;
            }
            $className = $config['payment/' . $code . '/model'];
            $country = $config['payment/' . $code . '/country'];
            $model = new $className();
            if ($model instanceof paymentAbstractMethod && $model->available(['total' => $codition['total']]) === true &&
                    (!$countryCode || !$country || in_array($countryCode, explode(',', $country)))) {
                $resultData[$code] = $this->translate($config['payment/' . $code . '/label'], [], null, $language['code']);
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get payment method successfully'];
        return $this->responseData;
    }

    /**
     * @param string $id
     * @param string $token
     * @param array $data
     * @return array
     */
    public function chargeBalanceOrder($id, $token, $data = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');

        $language = new Language();
        $language->load($languageId);
        $additional = [];
        if ($config['balance/general/enable'] && $config['balance/general/product_for_recharge']) {
            $amount = empty($data['amount']) ? $data['amount'] : 1;

            $product = new Product();
            $product->load($config['balance/general/product_for_recharge']);

            $base = $config['i18n/currency/base'];
            if ($currencyCode == '') {
                $currencyCode = $base;
            }
            $currency = new Currency();
            $currency->load($currencyCode, 'code');

            $items = [];
            $items[] = [
                'product_id' => $config['balance/general/product_for_recharge'],
                'product_name' => $product['product_name'],
                'options' => '',
                'options_name' => '',
                'options_image' => '',
                'qty' => $amount,
                'store_id' => $product['store_id'],
                'sku' => $product['sku'],
                'is_virtual' => $product['is_virtual'],
                'base_price' => 1,
                'price' => $currency->convert(1),
                'base_discount' => 0,
                'discount' => 0,
                'base_tax' => 0,
                'tax' => 0,
                'base_total' => $amount,
                'total' => $currency->convert($amount),
                'warehouse_id' => 1,
                'weight' => 0
            ];
            $customer = new Customer();
            $customer->load($data['customer_id']);
            $paymentMethod = $this->validPayment(['total' => $amount] + $data);

            $key = '1-' . $product['store_id'];
            $orders = [];
            $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
            $orderArray = [
                'status_id' => $paymentMethod->getNewOrderStatus(),
                'customer_id' => $customer->getId(),
                'language_id' => $languageId,
                'billing_address_id' => '',
                'shipping_address_id' => '',
                'warehouse_id' => 1,
                'base_total_refunded' => 0,
                'store_id' => $product['store_id'],
                'billing_address' => '',
                'shipping_address' => '',
                'total_refunded' => 0,
                'is_virtual' => 1,
                'free_shipping' => '',
                'base_currency' => $baseCurrency,
                'currency' => $currencyCode,
                'base_subtotal' => $amount,
                'shipping_method' => '',
                'payment_method' => $data['payment_method'],
                'base_shipping' => 0,
                'shipping' => 0,
                'subtotal' => $amount,
                'base_discount' => 0,
                'discount' => 0,
                'discount_detail' => '',
                'base_tax' => 0,
                'tax' => 0,
                'base_total' => $amount,
                'total' => $currency->convert($amount),
                'base_total_paid' => 0,
                'total_paid' => 0,
                'customer_note' => '',
                'coupon' => '',
                'additional' => json_encode($additional)
            ];
            $order = new Model($orderArray);
            $orders[$key] = $order->save();
            $orderId = $order->getId();

            foreach ($items as $item) {
                $item = new orderItem($item);
                $item->setData('order_id', $orderId)->setId(null)->save();
            }
            $this->responseData = ['statusCode' => '200', 'data' => ['order_id' => $orderId, 'total' => $currency->convert($amount)], 'message' => 'create charge order successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Invalid product_for_recharge'];
            return $this->responseData;
        }
    }

}
