<?php

namespace Redseanet\Sales\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Sales\Model\Collection\Cart as Collection;
use Redseanet\Sales\Model\Cart as Model;
use Redseanet\Sales\Model\Collection\Cart\Item as ItemCollection;
use Laminas\Db\Sql\Where;
use Redseanet\Lib\Bootstrap;
use Redseanet\Resource\Model\Resource;
use Redseanet\Lib\Db\YsInsert;
use Redseanet\Api\Model\Rpc\{
    User
};
use Redseanet\Lib\Session\Segment;
use Redseanet\Catalog\Model\Product;
use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Wishlist;
use Redseanet\Customer\Model\Wishlist\WishlistItem;
use Redseanet\I18n\Model\Currency;
use Redseanet\Catalog\Exception\OutOfStock;
use Redseanet\Lib\Model\Language;
use Redseanet\I18n\Model\Locate;
use Redseanet\Payment\Model\AbstractMethod as paymentAbstractMethod;
use Redseanet\Shipping\Model\AbstractMethod as shippingAbstractMethod;
use Redseanet\Customer\Model\Address;
use Redseanet\Promotion\Model\Collection\Rule;

class Cart extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;

    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Lib\Traits\Translate;
    use \Redseanet\Checkout\Traits\Checkout;
    use \Redseanet\RewardPoints\Traits\Calc;
    use \Redseanet\Balance\Traits\Calc;

    /**
     * @var int
     */
    public $billing_address_id;

    /**
     * @var int
     */
    public $shipping_address_id;

    /**
     * @var string
     */
    public $billing_address;

    /**
     * @var string
     */
    public $shipping_address;

    /**
     * @var bool
     */
    public $is_virtual;

    /**
     * @var bool
     */
    public $free_shipping;

    /**
     * @var string
     */
    public $coupon;

    /**
     * @var string
     */
    public $base_currency = '';

    /**
     * @var string
     */
    public $currency = '';

    /**
     * @var string
     */
    public $shipping_method;

    /**
     * @var string
     */
    public $payment_method;

    /**
     * @var float
     */
    public $base_subtotal;

    /**
     * @var float
     */
    public $subtotal;

    /**
     * @var float
     */
    public $base_shipping;

    /**
     * @var float
     */
    public $shipping;

    /**
     * @var float
     */
    public $base_discount;

    /**
     * @var float
     */
    public $discount;

    /**
     * @var string
     */
    public $discount_detail;

    /**
     * @var float
     */
    public $base_tax;

    /**
     * @var float
     */
    public $tax;

    /**
     * @var float
     */
    public $base_total;

    /**
     * @var float
     */
    public $total;

    /**
     * @var string
     */
    public $additional;

    /**
     * @var string
     */
    public $customer_note;

    /**
     * @var bool
     */
    public $status;

    /**
     * @var \Redseanet\Sales\Model\Api\Soap\CartItem[]
     */
    public $items;

    /**
     * @param int $customerId
     * @return Model
     */
    protected function getCart($customerId) {
        $segment = new Segment('customer');
        $segment->set('hasLoggedIn', true)
                ->set('customer', (new Customer())->setId($customerId));
        return Model::instance();
    }

    /**
     * @param string $id
     * @param string $token
     * @param string $cutomerId
     * @param string $data [productId=> , qty=>, warehouseId=>, options => [], sku = '']
     * @param int $languageId
     * @param string $currencyCode
     * @return string
     */
    public function addItemToCart($id, $token, $customerId, $data, $languageId = '', $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!isset($data['product_id']) || $data['product_id'] == '') {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'product_id data can not be null'];
            return $this->responseData;
        }
        if (!isset($data['qty']) || $data['qty'] == '') {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'qty data can not be null'];
            return $this->responseData;
        }
        if (!isset($data['warehouse_id']) || $data['warehouse_id'] == '') {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'warehouse_id data can not be null'];
            return $this->responseData;
        }
        if (empty($customerId)) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
        $language = new Language();
        $language->load($languageId);
        try {
            if ($languageId != '') {
                $product = new Product($languageId);
            } else {
                $product = new Product();
            }
            $product->load($data['product_id']);
            if (!empty($data['options']) && is_string($data['options'])) {
                $options = @json_decode($data['options'], true);
                if (!empty($options)) {
                    $data['options'] = $options;
                }
            }
            if (is_array($data['options']) && count($data['options']) > 0) {
                $_tmpOptions = [];
                foreach ($data['options'] as $key => $value) {
                    if (is_array($value)) {
                        sort($value);
                        $_value = [];
                        if (count($value) > 0) {
                            foreach ($value as $v_value) {
                                $_value[] = intval($v_value);
                            }
                        }
                        $_tmpOptions[$key] = $_value;
                    } else {
                        $_tmpOptions[$key] = [intval($value)];
                    }
                }
                ksort($_tmpOptions);
                $data['options'] = $_tmpOptions;
            }
            $productOptions = $product->getOptions(['is_required' => 1], $languageId);
            $productOptionsNames = [];

            foreach ($productOptions as $option) {
                if (!isset($data['options'][$option->getId()])) {
                    if (!isset($data['warehouse_id']) || $data['warehouse_id'] == '') {
                        $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The ' . $option->offsetGet('title') . ' field is required and cannot be empty.'];
                        return $this->responseData;
                    }
                } else {
                    $option->offsetGet('title');
                    if ($option->offsetGet('value') != '') {
                        $value = $option->offsetGet('value');
                        if (count($value) > 0) {
                            for ($i = 0; $i < count($value); $i++) {
                                if (in_array($value[$i]['id'], $data['options'][$option->getId()])) {
                                    if ($option->offsetGet('title')) {
                                        $productOptionsNames[] = $option->offsetGet('title') . ':' . ($value[$i]['title'] != '' ? $value[$i]['title'] : $value[$i]['default_title']);
                                    } else {
                                        $productOptionsNames[] = $option->offsetGet('default_title') . ':' . ($value[$i]['title'] != '' ? $value[$i]['title'] : $value[$i]['default_title']);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!isset($data['image']) || $data['image'] == '') {
                $data['image'] = '';
                if (isset($data['options']) && is_array($data['options']) && count($data['options']) > 0) {
                    $images = $product['images'];
                    if ($images) {
                        foreach ($data['options'] as $id => $value) {
                            if (is_array($value)) {
                                foreach ($value as $value_i) {
                                    $_value = $product->getValueData($id, $value_i);
                                    if (!empty($_value["sku"])) {
                                        foreach ($images as $image) {
                                            if ($image['group'] == $_value["sku"]) {
                                                $data['image'] = $image['name'];
                                                break;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $_value = $product->getValueData($id, $value_i);
                                if (!empty($_value["sku"])) {
                                    foreach ($images as $image) {
                                        if ($image['group'] == $_value["sku"]) {
                                            $data['image'] = $image['name'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if (empty($data['image']) && $product['thumbnail'] != '') {
                    $resource = new Resource();
                    $resource->load($product['thumbnail']);
                    $data['image'] = $resource['real_name'];
                }
            }
            $config = $this->getContainer()->get('config');
            $base = $config['i18n/currency/base'];
            if ($currencyCode == '') {
                $currencyCode = $base;
            }
            $currency = new Currency();
            $currency->load($currencyCode, 'code');
            $cart = $this->getCart($customerId);
            $option_value_id_string = '';
            if (is_array($data["options"]) && count($data["options"]) > 0) {
                $option_value_id_string = base64_encode(json_encode($data["options"]));
            }
            $cart->addItem($data['product_id'], $data['qty'], $data['warehouse_id'], isset($data['options']) ?
                            (is_string($data['options']) ? json_decode($data['options'], true) : (array) $data['options']) : [], $option_value_id_string, true, $languageId, implode(',', $productOptionsNames), $data['image']);
            $cart->collateTotals();
            $this->flushList('sales_cart');
            $this->flushList('sales_cart_item');
            $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'add the product into cart successfully'];
            return $this->responseData;
        } catch (ClickFarming $e) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Click farming check failed'];
            return $this->responseData;
        } catch (OutOfStock $e) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The requested quantity for ' . ((new Product())->load($data['product_id'])['name']) . ' is not available'];
            return $this->responseData;
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Prohibit the purchase of goods sold.'];
            $this->getContainer()->get('log')->logException($e);
            return $this->responseData;
        }
    }

    /**
     * @param int $id api user id
     * @param string $token
     * @param int $customerId
     * @param bool $withItems
     * @param int $languageId
     * @param string $currencyCode
     * @return array
     */
    public function cartInfo($id, $token, $customerId, $withItems = false, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, $withItems, $languageId, $currencyCode), 'message' => 'get cart information successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
    }

    public function getCartData($customerId, $withItems = false, $languageId = 0, $currencyCode = '') {
        $cart = $this->getCart($customerId);
        $config = $this->getContainer()->get('config');
        if ($currencyCode == '') {
            $currencyCode = $config['i18n/currency/base'];
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $language = new Language();
        $language->load($languageId);
        $address = $cart->getShippingAddress();
        $countryCode = $address ? (new Locate())->getCode('country', $address->offsetGet('country')) : '';
        $resultData = $cart->toArray();
        $items = new ItemCollection();
        if ($withItems) {
            $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
            $items->where(['cart_id' => $cart->getId()]);
            //Bootstrap::getContainer()->get("log")->logException(new \Exception($items->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform())));
            $items->load(true, true);
            $resultData['items'] = [];
            $resultData["store_cart_info"] = [];
            foreach ($items as $item) {
                if (isset($item['image']) && $item['image'] != '') {
                    $image = $this->getResourceUrl('image/' . $item['image']);
                } else {
                    $image = $this->getPubUrl('frontend/images/placeholder.png');
                }
                unset($item['image']);
                $item['image'] = $image;
                //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($item)));
                $item['price'] = $currency->convert($item['base_price']);
                $item['total'] = $currency->convert($item['base_total']);
                if (isset($resultData['items'][$item['store_id']])) {
                    $resultData['items'][$item['store_id']][] = $item;
                } else {
                    $resultData['items'][$item['store_id']] = [];
                    $resultData['items'][$item['store_id']][] = $item;
                }
                $resultData['item_qty'] = !empty($resultData['item_qty']) ? ($resultData['item_qty'] + $item["qty"]) : $item["qty"];
                $resultData["store_cart_info"][$item['store_id']]["item_qty"] = !empty($resultData["store_cart_info"][$item['store_id']]["item_qty"]) ? ($resultData["store_cart_info"][$item['store_id']]["item_qty"] + $item["qty"]) : floatval($item["qty"]);
                $resultData["store_cart_info"][$item['store_id']]["base_total"] = !empty($resultData["store_cart_info"][$item['store_id']]["base_total"]) ? ($resultData["store_cart_info"][$item['store_id']]["base_total"] + $item["base_total"]) : $item['base_total'];
                $resultData["store_cart_info"][$item['store_id']]["total"] = !empty($resultData["store_cart_info"][$item['store_id']]["total"]) ? ($resultData["store_cart_info"][$item['store_id']]["total"] + $item["base_total"]) : $item['base_total'];
                $resultData["store_cart_info"][$item['store_id']]["base_discount"] = !empty($resultData["store_cart_info"][$item['store_id']]["base_discount"]) ? ($resultData["store_cart_info"][$item['store_id']]["base_discount"] + $item["base_discount"]) : $item['base_discount'];
                $resultData["store_cart_info"][$item['store_id']]["discount"] = !empty($resultData["store_cart_info"][$item['store_id']]["discount"]) ? ($resultData["store_cart_info"][$item['store_id']]["discount"] + $item["discount"]) : $item['discount'];
                $resultData["store_cart_info"][$item['store_id']]["weight"] = !empty($resultData["store_cart_info"][$item['store_id']]["weight"]) ? ($resultData["store_cart_info"][$item['store_id']]["weight"] + $item["weight"]) : $item['weight'];
                $resultData["store_cart_info"][$item['store_id']]["base_tax"] = !empty($resultData["store_cart_info"][$item['store_id']]["base_tax"]) ? ($resultData["store_cart_info"][$item['store_id']]["base_tax"] + $item["base_tax"]) : $item['base_tax'];
                $resultData["store_cart_info"][$item['store_id']]["tax"] = !empty($resultData["store_cart_info"][$item['store_id']]["tax"]) ? ($resultData["store_cart_info"][$item['store_id']]["tax"] + $item["tax"]) : $item['tax'];
            }
        }
        $time = time();
        $rules = new Rule();
        $rules->withStore(true)
                ->where(['use_coupon' => 1, 'status' => 1])
                ->order('sort_order');
        $resultData["coupons"] = [];
        foreach ($resultData['items'] as $store => $items) {
            if ($cart->isVirtual($store)) {
                $resultData["store_cart_info"][$store]["shipping_method_list"] = [];
                $resultData["store_cart_info"][$store]["isvirtal"] = true;
            } else {
                $resultData["store_cart_info"][$store]["isvirtal"] = false;
                foreach ($config['system']['shipping']['children'] as $code => $info) {
                    if (!empty($code)) {
                        $country = $config['shipping/' . $code . '/country'];
                        $className = $config['shipping/' . $code . '/model'];
                        $model = new $className();
                        if ($model instanceof shippingAbstractMethod && $model->available(['total' => $resultData["store_cart_info"][$store]["base_total"]]) &&
                                (!$countryCode || !$country || in_array($countryCode, explode(',', $country)))) {
                            $resultData["store_cart_info"][$store]["shipping_method_list"][] = ['code' => $code, 'label' => $this->translate($config['shipping/' . $code . '/label'], [], null, $language['code']), 'fee' => $model->getShippingRate($items)];
                        }
                    }
                }
            }
            foreach ($rules as $rule) {
                $condition = $rule->getCondition();
                if (
                        empty($rule->offsetGet('store_id')) ||
                        in_array($store, (array) $rule->offsetGet('store_id'))
                ) {
                    if ((empty($rule->offsetGet('from_date')) || $time >= strtotime($rule->offsetGet('from_date'))) &&
                            (empty($rule->offsetGet('to_date')) || $time <= strtotime($rule->offsetGet('to_date'))) &&
                            (empty($condition) ||
                            $condition->match($cart, $store))) {
                        foreach ($rule->getCoupon() as $coupon) {
                            if ($rule->matchCoupon($coupon['code'], $cart)) {
                                $resultData["coupons"][$store]["match"][] = [
                                    'id' => $rule->getId(),
                                    'code' => $coupon->offsetGet('code'),
                                    'promotion_id' => $coupon->offsetGet('promotion_id'),
                                    'title' => $rule->offsetGet('name'),
                                    'description' => $rule->offsetGet('description'),
                                    'store_id' => $store
                                ];
                                break;
                            }
                        }
                    } else {
                        $resultData["coupons"][$store]["unmatch"][] = [
                            'id' => $rule->getId(),
                            'code' => "",
                            'promotion_id' => $rule->getId(),
                            'title' => $rule->offsetGet('name'),
                            'description' => $rule->offsetGet('description')
                        ];
                    }
                } else {
                    
                }
            }
        }
        $resultData['payment_method_list'] = [];
        if ($resultData['base_total']) {
            foreach ($config['system']['payment']['children'] as $code => $info) {
                if ($code === 'payment_free') {
                    continue;
                }
                $className = $config['payment/' . $code . '/model'];
                $country = $config['payment/' . $code . '/country'];
                if (!is_array($country)) {
                    $country = explode(',', $country);
                }
                $model = new $className();
                if ($model instanceof paymentAbstractMethod && $model->available(['total' => $resultData['base_total']]) === true &&
                        (!$countryCode || !$country || in_array($countryCode, $country))) {
                    $resultData['payment_method_list'][] = ["code" => $code, "label" => $this->translate($config['payment/' . $code . '/label'], [], null, $language['code'])];
                }
            }
        } else {
            $resultData['payment_method_list'][] = ["code" => "payment_free", "label" => $config['payment/payment_free/label']];
        }

        $resultData['sub_total'] = $currency->convert(!empty($resultData['base_subtotal']) ? $resultData['base_subtotal'] : 0);
        $resultData['shipping'] = $currency->convert(!empty($resultData['base_shipping']) ? $resultData['base_shipping'] : 0);
        $resultData['shipping_method'] = !empty($resultData['shipping_method']) ? json_decode($resultData['shipping_method'], true) : [];
        $resultData['discount'] = $currency->convert(!empty($resultData['base_discount']) ? $resultData['base_discount'] : 0);
        $resultData['tax'] = $currency->convert(!empty($resultData['base_tax']) ? $resultData['base_tax'] : 0);
        $resultData['total'] = $currency->convert(!empty($resultData['base_total']) ? $resultData['base_total'] : 0);
        $resultData['currency'] = $currencyCode;
        $resultData['discount_detail'] = !empty($resultData['discount_detail']) ? json_decode($resultData['discount_detail'], true) : [];
        $resultData['additional'] = !empty($resultData['additional']) ? json_decode($resultData['additional'], true) : [];
        $resultData['coupon']=(!empty($resultData['coupon'])? json_decode($resultData['coupon']):[]);
        return $resultData;
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param int $itemId
     * @param float $qty
     * @param int $language
     * @param string $currencyCode
     * @return array
     */
    public function cartChangeItemQty($id, $token, $customerId, $itemId, $qty, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            try {
                $config = $this->getContainer()->get('config');
                $base = $config['i18n/currency/base'];
                if ($currencyCode == '') {
                    $currencyCode = $base;
                }
                $currency = new Currency();
                $currency->load($currencyCode, 'code');
                $language = new Language();
                $language->load($languageId);
                $cart = $this->getCart($customerId);
                $cart->changeQty($itemId, $qty);
                $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'change the item quantity successfully'];
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'change the item quantity failure'];
            }
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param array $itemIds
     * @param boolean $whetherFavorite
     * @return array
     */
    public function cartRemoveItem($id, $token, $customerId, $itemIds = [], $whetherFavorite = false, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $cart = $this->getCart($customerId);
        try {
            $config = $this->getContainer()->get('config');
            $base = $config['i18n/currency/base'];
            if ($currencyCode == '') {
                $currencyCode = $base;
            }
            $currency = new Currency();
            $currency->load($currencyCode, 'code');
            $language = new Language();
            $language->load($languageId);
            if (count($itemIds) > 0) {
                for ($i = 0; $i < count($itemIds); $i++) {
                    $data = $cart->getItem(intval($itemIds[$i]));
                    $cart->removeItem(intval($itemIds[$i]));
                    if ($whetherFavorite && !empty($data['product_id'])) {
                        $wishlist = new Wishlist();
                        $wishlist->load($customerId, 'customer_id');
                        if (!$wishlist->getId()) {
                            $wishlist->load($wishlist->getId())->setData(['customer_id' => $customerId, 'id' => null])->save();
                        }

                        $data['wishlist_id'] = $wishlist->getId();
                        //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($data)));
                        $wishlist->addItem($data);
                    }
                }
                $cart->collateTotals();
                $this->responseData = ['statusCode' => '403', "data" => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'remove the item successfully'];
                return $this->responseData;
            } else {
                $this->responseData = ['statusCode' => '403', "data" => [], 'message' => 'items can not bet null'];
            }
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '403', "data" => [], 'message' => 'remove the item failure'];
        }
        return $this->responseData;
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param int $itemId
     * @param float $qty
     * @param int $language
     * @param string $currencyCode
     * @return array
     */
    public function cartChangeItemStatus($id, $token, $customerId, $ids = [], $actionType = 1, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            try {
                $config = $this->getContainer()->get('config');
                $base = $config['i18n/currency/base'];
                if ($currencyCode == '') {
                    $currencyCode = $base;
                }
                $currency = new Currency();
                $currency->load($currencyCode, 'code');
                $language = new Language();
                $language->load($languageId);
                $cart = $this->getCart($customerId);
                for ($i = 0; $i < count($ids); $i++) {
                    $item = $cart->getItem(intval($ids[$i]));
                    if ($actionType == 1) {
                        $actionType = 1;
                    } else {
                        $actionType = 0;
                    }
                    $cart->changeItemStatus($item, $actionType, true);
                }
                $this->responseData = ['statusCode' => '403', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'change the item quantity successfully'];
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'change the item quantity failure'];
            }
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
    }

    public function selectPayment($id, $token, $customerId, $data = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        $language = new Language();
        $language->load($languageId);
        $resultData = [];
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        try {
            $cart = $this->getCart($customerId);
            $paymentObject = $this->validPayment(['total' => $cart['base_total']] + $data);
            if (empty($paymentObject)) {
                $this->responseData = ['statusCode' => '403', 'data' => $resultData, 'message' => $this->translate('The payment method is invaild', [], null, $language['code'])];
                return $this->responseData;
            }
            $cart->setData([
                'payment_method' => $data['payment_method']
            ])->collateTotals();
            $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => $this->translate("select payment method successfully", [], null, $language['code'])];
            return $this->responseData;
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate($e->getMessage(), [], null, $language['code'])];
            return $this->responseData;
        }
    }

    public function selectShipping($id, $token, $customerId, $data = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            $config = $this->getContainer()->get('config');
            $cart = $this->getCart($customerId);
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
                foreach ($cart->getItems() as $item) {
                    if (!$item['is_virtual'] && $item['status'] && !isset($result[$item['store_id']])) {
                        if (!isset($data['shipping_method'][$item['store_id']])) {
                            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Please select shipping method'];
                            return $this->responseData;
                        }
                        $className = $config['shipping/' . preg_replace('/:[^:]+$/', '', $data['shipping_method'][$item['store_id']]) . '/model'];
                        $result[$item['store_id']] = new $className();
                        $method = $result[$item['store_id']]->available(['total' => $totals[$item['store_id']]]);
                        if (!$method) {
                            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Invalid shipping method'];
                            return $this->responseData;
                        }
                    }
                }
                $cart->setData([
                    'shipping_method' => json_encode($data['shipping_method'])
                ])->collateTotals();
            }
            $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'select shipping method successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
    }

    public function selectAddress($id, $token, $customerId, $data = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            $cart = $this->getCart($customerId);
            $address = new Address();
            $address->load($data['shipping_address_id']);
            if (!$address->offsetGet('customer_id') || $address->offsetGet('customer_id') != $customerId) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Invalid address ID'];
                return $this->responseData;
            }
            if ($cart->isVirtual()) {
                if ($address) {
                    $cart->setData([
                        'billing_address_id' => $data['billing_address_id'],
                        'billing_address' => $address->display(false)
                    ])->collateTotals();
                }
            } else {
                $cart->setData([
                    'shipping_address_id' => $data['shipping_address_id'],
                    'shipping_address' => $address->display(false)
                ])->setData($address ? [
                            'billing_address_id' => $data['billing_address_id'],
                            'billing_address' => $address->display(false)
                                ] : [
                            'billing_address_id' => $data['shipping_address_id'],
                            'billing_address' => $address->display(false)
                        ])->collateTotals();
            }
            $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'select shiiping address successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
    }

    public function selectCoupon($id, $token, $customerId, $data = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            $cart = $this->getCart($customerId);
            try {
                $this->getContainer()->get('eventDispatcher')->trigger('promotion.apply', ['model' => $cart]);
                $cart->setData([
                    'coupon' => json_encode($data['coupon'])
                ])->collateTotals();

                $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'coupon apply successfully'];
                return $this->responseData;
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'coupon apply unsuccessfully'];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'coupon apply unsuccessfully'];
            return $this->responseData;
        }
    }

    public function getAvailablePoints($id, $token, $customerId, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            $cart = $this->getCart($customerId);
            $returnData = $this->getPoints($cart);
            $this->responseData = ['statusCode' => '200', 'data' => $returnData, 'message' => 'get available point successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'get points unsuccessfully'];
            return $this->responseData;
        }
    }

    public function getAvailableBalances($id, $token, $customerId, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            $cart = $this->getCart($customerId);
            $returnData = $this->getBalances($cart);
            $this->responseData = ['statusCode' => '200', 'data' => $returnData, 'message' => 'get available balance successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'get balance unsuccessfully'];
            return $this->responseData;
        }
    }

    public function applyBalances($id, $token, $customerId, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            try {
                $cart = $this->getCart($customerId);
                $this->getContainer()->get('eventDispatcher')->trigger('balances.apply', ['model' => $cart]);
                $cart->collateTotals();
                $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'balances apply successfully'];
                return $this->responseData;
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'balances apply unsuccessfully'];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'balances apply unsuccessfully'];
            return $this->responseData;
        }
    }

    public function cancelBalances($id, $token, $customerId, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            try {
                $cart = $this->getCart($customerId);
                $this->getContainer()->get('eventDispatcher')->trigger('balances.cancel', ['model' => $cart]);
                $cart->collateTotals();
                $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'balances apply cancel successfully'];
                return $this->responseData;
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'balances apply cancel unsuccessfully'];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'balances apply cancel unsuccessfully'];
            return $this->responseData;
        }
    }

    public function applyRewardPoints($id, $token, $customerId, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            try {
                $cart = $this->getCart($customerId);
                $this->getContainer()->get('eventDispatcher')->trigger('rewardpoints.apply', ['model' => $cart]);
                $cart->collateTotals();
                $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'reward points apply successfully'];
                return $this->responseData;
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'reward points apply unsuccessfully'];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'reward points apply unsuccessfully'];
            return $this->responseData;
        }
    }

    public function cancelRewardPoints($id, $token, $customerId, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            try {
                $cart = $this->getCart($customerId);
                $this->getContainer()->get('eventDispatcher')->trigger('rewardpoints.cancel', ['model' => $cart]);
                $cart->collateTotals();
                $this->responseData = ['statusCode' => '200', 'data' => $this->getCartData($customerId, true, $languageId, $currencyCode), 'message' => 'reward points apply cancel successfully'];
                return $this->responseData;
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'reward points apply cancel unsuccessfully'];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'reward points apply cancel unsuccessfully'];
            return $this->responseData;
        }
    }

}
