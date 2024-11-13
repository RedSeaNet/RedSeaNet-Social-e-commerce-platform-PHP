<?php

namespace Redseanet\Bulk\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\AbstractHandler;
use Redseanet\Bulk\Model\Collection\Bulk as Collection;
use Redseanet\Bulk\Model\Bulk as Model;
use Redseanet\Bulk\Model\Bulk\Item;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Model\Eav\Attribute\Set;
use Redseanet\Catalog\Model\Collection\Product as productCollection;
use Redseanet\I18n\Model\Currency;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Language;
use Redseanet\Catalog\Model\Product;
use Redseanet\Customer\Model\Customer;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Item as orderItem;
use Redseanet\Bulk\Model\Collection\Bulk\Item as itemCollection;

class Bulk extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Filter;

    use \Redseanet\Lib\Traits\Url;

    use \Redseanet\Lib\Traits\Translate;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Checkout\Traits\Checkout;

    public function bulkList($id, $token, $conditionData = [], $customerId = '', $languageId = 0, $currencyCode = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $resultData = [];
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $products = new productCollection($languageId);
        $products->where("bulk_price!='' and status=1");
        ///echo $products->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        $prductsArray = $products->load(true, true);
        $tmpResultData = [];
        if (count($prductsArray) > 0) {
            for ($i = 0; count($prductsArray) > $i; $i++) {
                $imageReturnData = [];
                if ($prductsArray[$i]['images'] != '') {
                    $imagesArray = json_decode($prductsArray[$i]['images'], true);
                    if (count($imagesArray) > 0) {
                        for ($ii = 0; count($imagesArray) > $ii; $ii++) {
                            if (isset($imagesArray[$ii]['name']) && $imagesArray[$ii]['name'] != '') {
                                $imageReturnData[] = ['label' => $imagesArray[$ii]['label'], 'id' => $imagesArray[$ii]['id'], 'name' => $imagesArray[$ii]['name'], 'group' => $imagesArray[$ii]['group'], 'src' => $this->getResourceUrl('image/' . $imagesArray[$ii]['name'])];
                            }
                        }
                    }
                }
                unset($prductsArray[$i]['images']);
                $prductsArray[$i]['images'] = $imageReturnData;
                $prductsArray[$i]['price'] = $currency->convert($prductsArray[$i]['price']);
                $prductsArray[$i]['msrp'] = $currency->convert($prductsArray[$i]['msrp']);
                $bulk_price = json_decode($prductsArray[$i]['bulk_price'], true);
                $new_bulk_price = [];
                if (count($bulk_price) > 0) {
                    foreach ($bulk_price as $bulk_key => $bulk_value) {
                        $new_bulk_price[$bulk_key] = $currency->convert($bulk_value);
                    }
                }
                $prductsArray[$i]['bulk_price'] = $new_bulk_price;
                $tmpResultData[] = $prductsArray[$i];
            }
        }
        $resultData['products'] = $tmpResultData;
        $resultData['currency'] = $currency->toArray();
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get bulk data successfully'];
        return $this->responseData;
    }

    public function bulkSalesList($id, $token, $productId, $customerId = '', $activeOnly = 0, $languageId = 0, $currencyCode = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $resultData = [];
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');

        $collection = new Collection();
        $collection->join('bulk_sale_item', 'bulk_sale_item.bulk_id=bulk_sale.id', ['options', 'options_name', 'options_image', 'qty',
            'sku', 'is_virtual', 'free_shipping', 'base_price', 'price',
            'base_discount', 'discount', 'base_tax', 'tax', 'base_total',
            'total', 'weight', 'store_id', 'warehouse_id'], 'left')
                ->where(['bulk_sale_item.product_id' => $productId])
                ->order('created_at DESC');
        if ($activeOnly) {
            $collection->where(['bulk_sale.status' => 1]);
            if ($config['catalog/bulk_sale/limitation']) {
                $collection->getSelect()->where->lessThan('count', 'size', 'identifier', 'identifier');
            }
        }
        $bulk_sales = [];
        $language = new Language();
        $language->load($languageId);
        foreach ($collection as $bulk) {
            $bulk = $bulk->toArray();
            $progress = min((int) ($bulk['count'] / $bulk['size'] * 100), 100);
            $bulk['progress'] = $progress;
            $bulk['sale_text'] = $this->translate('Creator: %s', [mb_substr($bulk['customer_name'], 0, 1) . '****' . mb_substr($bulk['customer_name'], -1)], null, $language['code']);
            $bulk['sale_time'] = date('m/d', strtotime($bulk['created_at']));
            $bulk['options_name'] = $bulk['options_name'];
            $bulk_sales[] = $bulk;
        }
        $resultData['sales'] = $bulk_sales;
        $resultData['currency'] = $currency->toArray();
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get bulk data successfully'];
        return $this->responseData;
    }

    public function bulkApply($id, $token, $productId, $customerId, $bulkId = '', $data = [], $languageId = 0, $currencyCode = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $resultData = [];
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $customer = new Customer();
        $customer->load($customerId);
        if (empty($data['options_name'])) {
            $data['options_name'] = '';
        }
        if ($bulkId != '') {
            try {
                $this->beginTransaction();
                $bulk = new Model();
                $bulk->load($bulkId);
                $bulkItemCollection = new itemCollection();
                $bulkItemCollection->where(['bulk_id' => $bulkId]);
                $bulkItemCollection->load(true, true);
                $product = new Product();
                $product->load($bulkItemCollection[0]['product_id']);
                $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];

                $items = [];
                $items[] = [
                    'product_id' => $bulkItemCollection[0]['product_id'],
                    'product_name' => $bulkItemCollection[0]['product_name'],
                    'options' => $bulkItemCollection[0]['options'],
                    'options_name' => $bulkItemCollection[0]['options_name'],
                    'qty' => $bulkItemCollection[0]['qty'],
                    'store_id' => $bulkItemCollection[0]['store_id'],
                    'sku' => $bulkItemCollection[0]['sku'],
                    'is_virtual' => $bulkItemCollection[0]['is_virtual'],
                    'base_price' => $bulkItemCollection[0]['base_price'],
                    'price' => $currency->convert($bulkItemCollection[0]['base_price']),
                    'base_discount' => 0,
                    'discount' => 0,
                    'base_tax' => 0,
                    'tax' => 0,
                    'base_total' => $bulkItemCollection[0]['base_total'],
                    'total' => $currency->convert($bulkItemCollection[0]['total']),
                    'warehouse_id' => $bulkItemCollection[0]['warehouse_id'],
                    'weight' => $bulkItemCollection[0]['weight']
                ];
                $billingAddress = $this->validBillingAddress($data);
                $paymentMethod = $this->validPayment(['total' => $bulkItemCollection[0]['base_total']] + $data);
                $base_shipping = 0;
                $shipping_method = $data['shipping_method'][$product['store_id']];
                $customer_note = isset($data['comment']) ? json_encode($data['comment']) : '{}';
                $billing_address_id = '';
                $billing_address = '';
                $shipping_address_id = '';
                $shipping_address = '';
                if ($bulkItemCollection[0]['is_virtual']) {
                    if ($billingAddress) {
                        $billing_address_id = $data['billing_address_id'];
                        $billing_address = $billingAddress->display(false);
                    }
                } else {
                    $shippingAddress = $this->validShippingAddress($data);
                    $shipping_address_id = $data['shipping_address_id'];
                    $shipping_address = isset($shippingAddress) ? $shippingAddress->display(false) : '';
                    if ($billingAddress) {
                        $billing_address_id = $data['billing_address_id'];
                        $billing_address = $billingAddress->display(false);
                    } else {
                        $billing_address_id = $data['shipping_address_id'];
                        $billing_address = $shippingAddress->display(false);
                    }
                    $base_shipping = $this->getShippingMethod($shipping_method)->getShippingRate($items);
                }

                $key = $data['warehouse_id'] . '-' . $product['store_id'];
                $orders = [];
                $additional = [];
                $additional['bulk'] = $bulk->getId();
                $discount_detail = ['Bulk' => ['id' => $bulk->getId(), 'detail' => $bulk]];
                $orderArray = [
                    'status_id' => $paymentMethod->getNewOrderStatus(),
                    'customer_id' => $customer->getId(),
                    'language_id' => $languageId,
                    'billing_address_id' => $billing_address_id,
                    'shipping_address_id' => $shipping_address_id,
                    'warehouse_id' => $data['warehouse_id'],
                    'base_total_refunded' => 0,
                    'store_id' => $product['store_id'],
                    'billing_address' => $billing_address,
                    'shipping_address' => $shipping_address,
                    'total_refunded' => 0,
                    'is_virtual' => $bulkItemCollection[0]['is_virtual'],
                    'free_shipping' => '',
                    'base_currency' => $baseCurrency,
                    'currency' => $currencyCode,
                    'base_subtotal' => $bulkItemCollection[0]['base_total'],
                    'shipping_method' => $shipping_method,
                    'payment_method' => $data['payment_method'],
                    'base_shipping' => $base_shipping,
                    'shipping' => $currency->convert($base_shipping),
                    'subtotal' => $currency->convert($bulkItemCollection[0]['base_total']),
                    'base_discount' => 0,
                    'discount' => 0,
                    'discount_detail' => json_encode($discount_detail),
                    'base_tax' => 0,
                    'tax' => 0,
                    'base_total' => $bulkItemCollection[0]['base_total'] + $base_shipping,
                    'total' => $currency->convert($bulkItemCollection[0]['base_total'] + $base_shipping),
                    'base_total_paid' => 0,
                    'total_paid' => 0,
                    'customer_note' => $customer_note,
                    'coupon' => '',
                    'additional' => json_encode($additional)
                ];
                $order = new Order($orderArray);
                $orders[$key] = $order->save();
                $orderId = $order->getId();

                foreach ($items as $item) {
                    $item = new orderItem($item);
                    $item->setData('order_id', $orderId)->setId(null)->save();
                }
                $result['redirect'] = $paymentMethod->preparePayment($orders);
                if (!empty($data['openid']) && $data['payment_method'] === \Redseanet\Payment\Model\WeChatPay::METHOD_CODE) {
                    $orders['openid'] = $data['openid'];
                }
                if (isset($orders['openid'])) {
                    $result['prepay'] = (new Segment('payment'))->get('wechatpay');
                }
                $this->commit();
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => $this->translate('join bulk successfully.')];
                return $this->responseData;
            } catch (ClickFarming $e) {
                $this->rollback();
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('Click farming check failed.')];
                return $this->responseData;
            } catch (OutOfStock $e) {
                $this->rollback();
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('The requested quantity for "%s" is not available.', [(new Product())->load($bulkItemCollection[0]['product_id'])['name']])];
                return $this->responseData;
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $this->rollback();
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('An error detected. Please contact us or try again later.')];
                return $this->responseData;
            }
        } else {
            //            $files = $this->getRequest()->getUploadedFile();
            //            if ($files) {
            //                $path = BP . 'pub/upload/bulk/';
            //                if (!is_dir($path)) {
            //                    mkdir($path, 0777, true);
            //                }
            //                foreach ($files['image'] as $file) {
            //                    if ($file->getError() === UPLOAD_ERR_OK && in_array($file->getClientMediaType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            //                        $newName = $file->getClientFilename();
            //                        while (file_exists($path . $newName)) {
            //                            $newName = preg_replace('/(\.[^\.]+$)/', random_int(0, 9) . '$1', $newName);
            //                            if (strlen($newName) >= 120) {
            //                                throw new Exception('The file is existed.');
            //                            }
            //                        }
            //                        $file->moveTo($path . $newName);
            //                        $data['description'] .= '<img src="' . $this->getBaseUrl('pub/upload/bulk/' . $newName) . '" />';
            //                    }
            //                }
            //            }

            try {
                $this->beginTransaction();
                if (!empty($data['options']) && is_string($data['options'])) {
                    $options = @json_decode($data['options'], true);
                    if (!empty($options)) {
                        $data['options'] = $options;
                    }
                }

                $product = new Product($languageId);
                $product->load($productId);
                $options = $product->getOptions(['is_required' => 1]);
                $sku = $product['sku'];
                //check require options
                foreach ($options as $option) {
                    if (!isset($data['options'][$option->getId()])) {
                        $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('The %s field is required and cannot be empty.', [$option->offsetGet('title')])];
                        return $this->responseData;
                    }
                }

                //get bulk price
                $prices = is_scalar($product['bulk_price']) ? json_decode($product['bulk_price'], true) : $product['bulk_price'];
                krsort($prices);
                $priceItem = $product['base_price'];
                foreach ($prices as $k => $p) {
                    if ($data['size'] >= $k) {
                        $priceItem = $p;
                        break;
                    }
                }
                //get sku and option price
                $sum = 0;
                if (!empty($data['options'])) {
                    foreach ($options as $option) {
                        if (isset($data['options'][$option->getId()])) {
                            if (in_array($option->offsetGet('input'), ['select', 'radio', 'checkbox', 'multiselect'])) {
                                foreach ($option->getValues() as $value) {
                                    if ($value['id'] == $data['options'][$option->getId()]) {
                                        $sum += $value['is_fixed'] ? $value['price'] : $priceItem * $value['price'] / 100;
                                    }
                                }
                            } else {
                                $sum += $option['is_fixed'] ? $option['price'] : $priceItem * $option['price'] / 100;
                            }
                        }
                    }
                }

                $item_base_price = max(0, $priceItem + $sum);
                $item_base_price_total = $item_base_price * $data['qty'];

                $isVirtual = $product->isVirtual() ? 1 : 0;
                $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
                $currencyCode = $this->getContainer()->get('request')->getCookie('currency', $baseCurrency);
                $currency = (new Currency())->load($currencyCode, 'code');
                $items = [];
                $items[] = [
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'options' => is_string($data['options']) ? $data['options'] : json_encode($data['options']),
                    'options_name' => $data['options_name'],
                    'qty' => $data['qty'],
                    'store_id' => $product['store_id'],
                    'sku' => $sku,
                    'is_virtual' => $isVirtual,
                    'base_price' => $item_base_price,
                    'price' => $currency->convert($item_base_price),
                    'base_discount' => 0,
                    'discount' => 0,
                    'base_tax' => 0,
                    'tax' => 0,
                    'base_total' => $item_base_price_total,
                    'total' => $currency->convert($item_base_price_total),
                    'warehouse_id' => $data['warehouse_id'],
                    'weight' => $product['weight'] * $data['qty']
                ];

                $billingAddress = $this->validBillingAddress($data);
                $paymentMethod = $this->validPayment(['total' => $item_base_price_total] + $data);
                $base_shipping = 0;
                $shipping_method = $data['shipping_method'][$product['store_id']];

                $customer_note = isset($data['comment']) ? json_encode($data['comment']) : '{}';
                $billing_address_id = '';
                $billing_address = '';
                $shipping_address_id = '';
                $shipping_address = '';
                if ($isVirtual) {
                    if ($billingAddress) {
                        $billing_address_id = $data['billing_address_id'];
                        $billing_address = $billingAddress->display(false);
                    }
                } else {
                    $shippingAddress = $this->validShippingAddress($data);
                    $shipping_address_id = $data['shipping_address_id'];
                    $shipping_address = isset($shippingAddress) ? $shippingAddress->display(false) : '';
                    if ($billingAddress) {
                        $billing_address_id = $data['billing_address_id'];
                        $billing_address = $billingAddress->display(false);
                    } else {
                        $billing_address_id = $data['shipping_address_id'];
                        $billing_address = $shippingAddress->display(false);
                    }
                    $base_shipping = $this->getShippingMethod($shipping_method)->getShippingRate($items);
                }
                $bulk = new Model([
                    'customer_id' => $customer->getId(),
                    'customer_name' => $customer->offsetGet('username'),
                    'size' => $data['size'],
                    'count' => 0,
                    'description' => isset($data['description']) ? $data['description'] : ''
                ]);
                $bulk->save();
                $discount_detail = ['Bulk' => ['id' => $bulk->getId(), 'detail' => $bulk]];
                $bulkItem = new Item([
                    'bulk_id' => $bulk->getId(),
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'options' => is_string($data['options']) ? $data['options'] : json_encode($data['options']),
                    'options_name' => $data['options_name'],
                    'base_discount' => 0,
                    'qty' => $data['qty'],
                    'sku' => $sku,
                    'is_virtual' => $isVirtual,
                    'base_price' => $item_base_price,
                    'price' => $currency->convert($item_base_price),
                    'base_discount' => 0,
                    'discount' => 0,
                    'base_tax' => 0,
                    'tax' => 0,
                    'base_total' => $item_base_price_total,
                    'total' => $currency->convert($item_base_price_total),
                    'weight' => $product['weight'] * $data['qty'],
                    'store_id' => $product['store_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'customer_id' => $customer->getId()
                ]);
                $bulkItem->save();
                $key = $data['warehouse_id'] . '-' . $product['store_id'];
                $orders = [];
                $additional = [];
                $additional['bulk'] = $bulk->getId();

                $orderArray = [
                    'status_id' => $paymentMethod->getNewOrderStatus(),
                    'customer_id' => $customer->getId(),
                    'language_id' => $languageId,
                    'billing_address_id' => $billing_address_id,
                    'shipping_address_id' => $shipping_address_id,
                    'warehouse_id' => $data['warehouse_id'],
                    'base_total_refunded' => 0,
                    'store_id' => $product['store_id'],
                    'billing_address' => $billing_address,
                    'shipping_address' => $shipping_address,
                    'total_refunded' => 0,
                    'is_virtual' => $isVirtual,
                    'free_shipping' => '',
                    'base_currency' => $baseCurrency,
                    'currency' => $currencyCode,
                    'base_subtotal' => $item_base_price_total,
                    'shipping_method' => $shipping_method,
                    'payment_method' => $data['payment_method'],
                    'base_shipping' => $base_shipping,
                    'shipping' => $currency->convert($base_shipping),
                    'subtotal' => $currency->convert($item_base_price_total),
                    'base_discount' => 0,
                    'discount' => 0,
                    'discount_detail' => json_encode($discount_detail),
                    'base_tax' => 0,
                    'tax' => 0,
                    'base_total' => $item_base_price_total + $base_shipping,
                    'total' => $currency->convert($item_base_price_total + $base_shipping),
                    'base_total_paid' => 0,
                    'total_paid' => 0,
                    'customer_note' => $customer_note,
                    'coupon' => '',
                    'additional' => json_encode($additional)
                ];
                $order = new Order($orderArray);
                $orders[$key] = $order->save();
                $orderId = $order->getId();

                foreach ($items as $item) {
                    $item = new orderItem($item);
                    $item->setData('order_id', $orderId)->setId(null)->save();
                }

                $result['redirect'] = $paymentMethod->preparePayment($orders);
                if (!empty($data['openid']) && $data['payment_method'] === \Redseanet\Payment\Model\WeChatPay::METHOD_CODE) {
                    $orders['openid'] = $data['openid'];
                }
                if (isset($orders['openid'])) {
                    $result['prepay'] = (new Segment('payment'))->get('wechatpay');
                }

                $this->commit();
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => $this->translate('new bulk successfully.')];
                return $this->responseData;
            } catch (ClickFarming $e) {
                $this->rollback();
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('Click farming check failed.')];
                return $this->responseData;
            } catch (OutOfStock $e) {
                $this->rollback();

                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('The requested quantity for "%s" is not available.', [(new Product())->load($data['product_id'])['name']])];
                return $this->responseData;
            } catch (Exception $e) {
                $this->rollback();

                $this->getContainer()->get('log')->logException($e);
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('An error detected. Please contact us or try again later.')];
                return $this->responseData;
            }
        }
        $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('An error detected. Please contact us or try again later.')];
        return $this->responseData;
    }
}
