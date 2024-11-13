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

class Cart extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Url;

    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Lib\Traits\Translate;
    use \Redseanet\Checkout\Traits\Checkout;

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
    protected function getCart($customerId)
    {
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
    public function addItemToCart($id, $token, $customerId, $data, $languageId = '', $currencyCode = '')
    {
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
            if (!isset($data['image']) || $data['image'] == '') {
                $data['image'] = '';
                if (!empty($data['options']) && is_string($data['options'])) {
                    $options = @json_decode($data['options'], true);
                    if (!empty($options)) {
                        $data['options'] = $options;
                        $images = $product['images'];
                        if ($images) {
                            foreach ($options as $id => $value) {
                                $value = $product->getOption($id, $value, $languageId);
                                foreach ($images as $image) {
                                    if ($image['group'] == $value) {
                                        $data['image'] = $image['name'];
                                    }
                                }
                            }
                        }
                    } else {
                        $options = [];
                    }
                } else {
                    $options = [];
                }
                if ($data['image'] == '' && $product['thumbnail'] != '') {
                    $resource = new Resource();
                    $resource->load($product['thumbnail']);
                    $data['image'] = $resource['real_name'];
                }
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
                                if ($value[$i]['id'] == $data['options'][$option->getId()]) {
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
            $config = $this->getContainer()->get('config');
            $base = $config['i18n/currency/base'];
            if ($currencyCode == '') {
                $currencyCode = $base;
            }
            $currency = new Currency();
            $currency->load($currencyCode, 'code');
            $cart = $this->getCart($customerId);

            $cart->addItem($data['product_id'], $data['qty'], $data['warehouse_id'], isset($data['options']) ?
                            (is_string($data['options']) ? json_decode($data['options'], true) : (array) $data['options']) : [], $data['sku'] ?? '', true, $languageId, implode(',', $productOptionsNames), $data['image']);
            $cart->collateTotals();
            $resultData = $cart->toArray();
            $items = new ItemCollection();
            $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
            $items->where(['cart_id' => $cart->getId()]);
            $items->load(true, true);

            $resultData['items'] = [];
            foreach ($items as $item) {
                if (isset($item['image']) && $item['image'] != '') {
                    $image = $this->getResourceUrl('image/' . $item['image']);
                } else {
                    $image = $this->getPubUrl('frontend/images/placeholder.png');
                }
                unset($item['image']);
                $item['image'] = $image;
                $item['price'] = $currency->convert($item['base_price']);
                $item['total'] = $currency->convert($item['base_total']);
                if (isset($resultData['items'][$item['store_id']])) {
                    $resultData['items'][$item['store_id']][] = $item;
                } else {
                    $resultData['items'][$item['store_id']] = [];
                    $resultData['items'][$item['store_id']][] = $item;
                }
            }
            $resultData['subtotal'] = $currency->convert($resultData['base_subtotal']);
            $resultData['shipping'] = $currency->convert($resultData['base_shipping']);
            $resultData['discount'] = $currency->convert($resultData['base_discount']);
            $resultData['tax'] = $currency->convert($resultData['base_tax']);
            $resultData['total'] = $currency->convert($resultData['base_total']);
            $resultData['currency'] = $currencyCode;
            $resultData['discount_detail'] = json_decode($resultData['discount_detail'], true);
            $resultData['additional'] = json_decode($resultData['additional'], true);

            $shipping_method = !empty($resultData['shipping_method']) ? json_decode($resultData['shipping_method'], true) : [];
            $shipping_method_array = [];
            if (is_array($shipping_method) && count($shipping_method) > 0) {
                foreach ($shipping_method as $key => $value) {
                    $shipping_method_array[$key] = ['code' => $value, 'label' => $this->translate($config['shipping/' . $value . '/label'], [], null, $language['code'])];
                }
            }
            $resultData['shipping_method'] = $shipping_method_array;
            $this->flushList('sales_cart');
            $this->flushList('sales_cart_item');
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => ($product['name']) . 'has been added to your shopping cart'];
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
    public function cartInfo($id, $token, $customerId, $withItems = false, $languageId = 0, $currencyCode = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($customerId)) {
            $cart = $this->getCart($customerId);
            $config = $this->getContainer()->get('config');
            $base = $config['i18n/currency/base'];
            if ($currencyCode == '') {
                $currencyCode = $base;
            }
            $currency = new Currency();
            $currency->load($currencyCode, 'code');
            $language = new Language();
            $language->load($languageId);
            $resultData = $cart->toArray();
            if ($withItems) {
                $items = new ItemCollection();
                $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
                $items->where(['cart_id' => $cart->getId()]);
                //Bootstrap::getContainer()->get("log")->logException(new \Exception($items->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform())));
                $items->load(true, true);
                $resultData['items'] = [];
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
                };
            }
            $resultData['subtotal'] = $currency->convert(!empty($resultData['base_subtotal']) ? $resultData['base_subtotal'] : 0);
            $resultData['shipping'] = $currency->convert(!empty($resultData['base_shipping']) ? $resultData['base_shipping'] : 0);
            $resultData['discount'] = $currency->convert(!empty($resultData['base_discount']) ? $resultData['base_discount'] : 0);
            $resultData['tax'] = $currency->convert(!empty($resultData['base_tax']) ? $resultData['base_tax'] : 0);
            $resultData['total'] = $currency->convert(!empty($resultData['base_total']) ? $resultData['base_total'] : 0);
            $resultData['currency'] = $currencyCode;
            $resultData['discount_detail'] = !empty($resultData['discount_detail']) ? json_decode($resultData['discount_detail'], true) : [];
            $resultData['additional'] = !empty($resultData['additional']) ? json_decode($resultData['additional'], true) : [];
            $shipping_method = !empty($resultData['shipping_method']) ? json_decode($resultData['shipping_method'], true) : [];
            $shipping_method_array = [];
            if (is_array($shipping_method) && count($shipping_method) > 0) {
                foreach ($shipping_method as $key => $value) {
                    $shipping_method_array[$key] = ['code' => $value, 'label' => $this->translate($config['shipping/' . $value . '/label'], [], null, $language['code'])];
                }
            }
            $resultData['shipping_method'] = $shipping_method_array;
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get cart information successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => $resultData, 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
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
    public function cartChangeItemQty($id, $token, $customerId, $itemId, $qty, $languageId = 0, $currencyCode = '')
    {
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
                $resultData = $cart->toArray();
                $items = new ItemCollection();
                $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
                $items->where(['cart_id' => $cart->getId()]);
                $items->load(true, true);
                $resultData['items'] = [];
                foreach ($items as $item) {
                    if (isset($item['image']) && $item['image'] != '') {
                        $image = $this->getResourceUrl('image/' . $item['image']);
                    } else {
                        $image = $this->getPubUrl('frontend/images/placeholder.png');
                    }
                    unset($item['image']);
                    $item['image'] = $image;
                    $item['price'] = $currency->convert($item['base_price']);
                    $item['total'] = $currency->convert($item['base_total']);
                    if (isset($resultData['items'][$item['store_id']])) {
                        $resultData['items'][$item['store_id']][] = $item;
                    } else {
                        $resultData['items'][$item['store_id']] = [];
                        $resultData['items'][$item['store_id']][] = $item;
                    }
                };
                $resultData['subtotal'] = $currency->convert($resultData['base_subtotal']);
                $resultData['shipping'] = $currency->convert($resultData['base_shipping']);
                $resultData['discount'] = $currency->convert($resultData['base_discount']);
                $resultData['tax'] = $currency->convert($resultData['base_tax']);
                $resultData['total'] = $currency->convert($resultData['base_total']);
                $resultData['currency'] = $currencyCode;
                $resultData['discount_detail'] = !empty($resultData['discount_detail']) ? json_decode($resultData['discount_detail'], true) : [];
                $resultData['additional'] = !empty($resultData['additional']) ? json_decode($resultData['additional'], true) : [];
                $shipping_method = !empty($resultData['shipping_method']) ? json_decode($resultData['shipping_method'], true) : [];
                $shipping_method_array = [];
                if (is_array($shipping_method) && count($shipping_method) > 0) {
                    foreach ($shipping_method as $key => $value) {
                        $shipping_method_array[$key] = ['code' => $value, 'label' => $this->translate($config['shipping/' . $value . '/label'], [], null, $language['code'])];
                    }
                }
                $resultData['shipping_method'] = $shipping_method_array;
                $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'change the item quantity successfully'];
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
    public function cartRemoveItem($id, $token, $customerId, $itemIds = [], $whetherFavorite = false, $languageId = 0, $currencyCode = '')
    {
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
                $resultData = $cart->toArray();
                $items = new ItemCollection();
                $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
                $items->where(['cart_id' => $cart->getId()]);
                $items->load(true, true);
                $resultData['items'] = [];
                foreach ($items as $item) {
                    if (isset($item['image']) && $item['image'] != '') {
                        $image = $this->getResourceUrl('image/' . $item['image']);
                    } else {
                        $image = $this->getPubUrl('frontend/images/placeholder.png');
                    }
                    unset($item['image']);
                    $item['image'] = $image;
                    $item['price'] = $currency->convert($item['base_price']);
                    $item['total'] = $currency->convert($item['base_total']);
                    if (isset($resultData['items'][$item['store_id']])) {
                        $resultData['items'][$item['store_id']][] = $item;
                    } else {
                        $resultData['items'][$item['store_id']] = [];
                        $resultData['items'][$item['store_id']][] = $item;
                    }
                }
                $resultData['subtotal'] = $currency->convert($resultData['base_subtotal']);
                $resultData['shipping'] = $currency->convert($resultData['base_shipping']);
                $resultData['discount'] = $currency->convert($resultData['base_discount']);
                $resultData['tax'] = $currency->convert($resultData['base_tax']);
                $resultData['total'] = $currency->convert($resultData['base_total']);
                $resultData['currency'] = $currencyCode;
                $resultData['discount_detail'] = !empty($resultData['discount_detail']) ? json_decode($resultData['discount_detail'], true) : [];
                $resultData['additional'] = !empty($resultData['additional']) ? json_decode($resultData['additional'], true) : [];
                $shipping_method = !empty($resultData['shipping_method']) ? json_decode($resultData['shipping_method'], true) : [];
                $shipping_method_array = [];
                if (is_array($shipping_method) && count($shipping_method) > 0) {
                    foreach ($shipping_method as $key => $value) {
                        $shipping_method_array[$key] = ['code' => $value, 'label' => $this->translate($config['shipping/' . $value . '/label'], [], null, $language['code'])];
                    }
                }
                $resultData['shipping_method'] = $shipping_method_array;
                $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'remove the item successfully'];
                return $this->responseData;
            } else {
                $this->responseData = ['statusCode' => '403', 'message' => 'items can not bet null'];
            }
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '403', 'message' => 'remove the item failure'];
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
    public function cartChangeItemStatus($id, $token, $customerId, $ids = [], $actionType = 1, $languageId = 0, $currencyCode = '')
    {
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

                $resultData = $cart->toArray();
                $items = new ItemCollection();
                $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
                $items->where(['cart_id' => $cart->getId()]);
                $items->load(true, true);
                $resultData['items'] = [];
                foreach ($items as $item) {
                    if (isset($item['image']) && $item['image'] != '') {
                        $image = $this->getResourceUrl('image/' . $item['image']);
                    } else {
                        $image = $this->getPubUrl('frontend/images/placeholder.png');
                    }
                    unset($item['image']);
                    $item['image'] = $image;
                    $item['price'] = $currency->convert($item['base_price']);
                    $item['total'] = $currency->convert($item['base_total']);
                    if (isset($resultData['items'][$item['store_id']])) {
                        $resultData['items'][$item['store_id']][] = $item;
                    } else {
                        $resultData['items'][$item['store_id']] = [];
                        $resultData['items'][$item['store_id']][] = $item;
                    }
                };
                $resultData['subtotal'] = $currency->convert($resultData['base_subtotal']);
                $resultData['shipping'] = $currency->convert($resultData['base_shipping']);
                $resultData['discount'] = $currency->convert($resultData['base_discount']);
                $resultData['tax'] = $currency->convert($resultData['base_tax']);
                $resultData['total'] = $currency->convert($resultData['base_total']);
                $resultData['currency'] = $currencyCode;
                $resultData['discount_detail'] = json_decode($resultData['discount_detail'], true);
                $resultData['additional'] = json_decode($resultData['additional'], true);
                $shipping_method = json_decode($resultData['shipping_method'], true);
                $shipping_method_array = [];
                if (is_array($shipping_method) && count($shipping_method) > 0) {
                    foreach ($shipping_method as $key => $value) {
                        $shipping_method_array[$key] = ['code' => $value, 'label' => $this->translate($config['shipping/' . $value . '/label'], [], null, $language['code'])];
                    }
                }
                $resultData['shipping_method'] = $shipping_method_array;
                $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'change the item quantity successfully'];
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'change the item quantity failure'];
            }
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
    }

    public function selectPayment($id, $token, $customerId, $data = [], $languageId = 0, $currencyCode = '')
    {
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
            $resultData = $cart->toArray();
            $items = new ItemCollection();
            $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
            $items->where(['cart_id' => $cart->getId()]);
            //Bootstrap::getContainer()->get("log")->logException(new \Exception($items->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform())));
            $items->load(true, true);
            $resultData['items'] = [];
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
            };
            $resultData['subtotal'] = $currency->convert(!empty($resultData['base_subtotal']) ? $resultData['base_subtotal'] : 0);
            $resultData['shipping'] = $currency->convert($resultData['base_shipping']);
            $resultData['discount'] = $currency->convert($resultData['base_discount']);
            $resultData['tax'] = $currency->convert($resultData['base_tax']);
            $resultData['total'] = $currency->convert($resultData['base_total']);
            $resultData['currency'] = $currencyCode;
            $resultData['discount_detail'] = json_decode($resultData['discount_detail'], true);
            $resultData['additional'] = json_decode($resultData['additional'], true);
            $shipping_method = !empty($resultData['shipping_method']) ? json_decode($resultData['shipping_method'], true) : [];
            $shipping_method_array = [];
            foreach ($shipping_method as $key => $value) {
                $shipping_method_array[$key] = ['code' => $value, 'label' => $this->translate($config['shipping/' . $value . '/label'], [], null, $language['code'])];
            }
            $resultData['shipping_method'] = $shipping_method_array;
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'change payment method successfully'];
            return $this->responseData;
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '403', 'data' => $resultData, 'message' => $this->translate($e->getMessage(), [], null, $language['code'])];
            return $this->responseData;
        }
    }

    public function selectShipping($id, $token, $customerId, $data = [], $languageId = 0, $currencyCode = '')
    {
        try {
            $config = $this->getContainer()->get('config');
            $cart = $this->getCart($customerId);
            $currency = new Currency();
            $currency->load($currencyCode, 'code');
            $language = new Language();
            $language->load($languageId);
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
                $shippingObject = $this->validShipping(['totals' => $totals] + $data);
                if (empty($shippingObject)) {
                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate('The shipping method is invaild', [], null, $language['code'])];
                    return $this->responseData;
                }
                $cart->setData([
                    'shipping_method' => json_encode($data['shipping_method'])
                ])->collateTotals();
            }
            $resultData = $cart->toArray();
            $items = new ItemCollection();
            $items->join('core_store', 'sales_cart_item.store_id=core_store.id', ['store_name' => 'name', 'store_code' => 'code', 'store_status' => 'status'], 'left');
            $items->where(['cart_id' => $cart->getId()]);
            //Bootstrap::getContainer()->get("log")->logException(new \Exception($items->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform())));
            $items->load(true, true);
            $resultData['items'] = [];
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
            }
            $resultData['subtotal'] = $currency->convert(!empty($resultData['base_subtotal']) ? $resultData['base_subtotal'] : 0);
            $resultData['shipping'] = $currency->convert($resultData['base_shipping']);
            $resultData['discount'] = $currency->convert($resultData['base_discount']);
            $resultData['tax'] = $currency->convert($resultData['base_tax']);
            $resultData['total'] = $currency->convert($resultData['base_total']);
            $resultData['currency'] = $currencyCode;
            $resultData['discount_detail'] = json_decode($resultData['discount_detail'], true);
            $resultData['additional'] = json_decode($resultData['additional'], true);
            $shipping_method = json_decode($resultData['shipping_method'], true);
            $shipping_method_array = [];
            foreach ($shipping_method as $key => $value) {
                $shipping_method_array[$key] = ['code' => $value, 'label' => $this->translate($config['shipping/' . $value . '/label'], [], null, $language['code'])];
            }
            $resultData['shipping_method'] = $shipping_method_array;
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'change shipping method successfully'];
            return $this->responseData;
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate($e->getMessage(), [], null, $language['code'])];
            return $this->responseData;
        }
    }
}
