<?php

namespace Redseanet\Bulk\Controller;

use Exception;
use Redseanet\Bulk\Exception\JoinedException;
use Redseanet\Bulk\Model\Bulk;
use Redseanet\Bulk\Model\Bulk\Item;
use Redseanet\Bulk\Model\Collection\Bulk\Item as itemCollection;
use Redseanet\Catalog\Exception\OutOfStock;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Exception\ClickFarming;
use Redseanet\Sales\Model\Cart;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Item as orderItem;
use Redseanet\Lib\Bootstrap;
use Redseanet\I18n\Model\Currency;

class ProcessController extends ActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Bulk\Traits\Cancel;
    use \Redseanet\Bulk\Traits\Refund;
    use \Redseanet\Checkout\Traits\Checkout;

    protected function doDispatch($method = 'notFoundAction')
    {
        $session = new Segment('customer');
        if (!$session->get('hasLoggedIn', false)) {
            $referer = $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'];
            return $this->redirect('customer/account/login/' . ($referer ? '?success_url=' .
                            str_replace(['+', '/', '='], ['-', '_', ''], urlencode($referer)) : ''));
        }
        return parent::doDispatch($method);
    }

    public function applyAction()
    {
        $data = $this->getRequest()->getPost();
        $bulk = null;
        $root = $this->getLayout('bulk_sale_apply');
        if (isset($data['bulk']) && $data['bulk'] != '') {
            $bulkObject = new Bulk();
            $bulkObject->load($data['bulk']);
            $itemCollectionObject = new itemCollection();
            $itemCollectionObject->where(['bulk_id' => $data['bulk']]);
            $itemCollectionObject->load(true, true);
            $bulk = $bulkObject;
            $product = new Product();
            $product->load($itemCollectionObject[0]['product_id']);
            $item = new Item([
                'product_id' => $itemCollectionObject[0]['product_id'],
                'product_name' => $itemCollectionObject[0]['product_name'],
                'store_id' => $itemCollectionObject[0]['store_id'],
                'qty' => $itemCollectionObject[0]['qty'],
                'is_virtual' => $product->isVirtual() ? 1 : 0,
                'options' => $itemCollectionObject[0]['options'],
                'options_name' => $itemCollectionObject[0]['options_name'],
                'sku' => $itemCollectionObject[0]['sku'],
                'warehouse_id' => $itemCollectionObject[0]['warehouse_id'],
                'weight' => $itemCollectionObject[0]['weight'],
                'base_price' => $itemCollectionObject[0]['base_price'],
                'price' => $itemCollectionObject[0]['price']
            ]);
            $root->getChild('main', true)->setVariable('bulk_item', $item);
        } else {
            $result = $this->validateForm($data, ['product_id', 'qty', 'warehouse_id']);
            if ($result['error'] === 0) {
                try {
                    if (!empty($data['options']) && is_string($data['options'])) {
                        $options = @json_decode($data['options'], true);
                        if (!empty($options)) {
                            $data['options'] = $options;
                        }
                    }
                    $product = new Product();
                    $product->load($data['product_id']);
                    $options = $product->getOptions(['is_required' => 1]);
                    $sku = $product['sku'];
                    foreach ($options as $option) {
                        if (!isset($data['options'][$option->getId()])) {
                            $value = $data['options'][$option->getId()];
                            if (in_array($option->offsetGet('input'), ['select', 'radio', 'checkbox', 'multiselect'])) {
                                $value = $option->getValue($value, false);
                                if ($value['sku'] !== '') {
                                    $sku .= '-' . $option->getValue($value, false)['sku'];
                                }
                            } elseif ($value !== '' && $option['sku'] !== '') {
                                $sku .= '-' . $option['sku'];
                            }
                            $result['error'] = 1;
                            $result['message'][] = ['message' => sprintf($this->translate('The %s field is required and cannot be empty.'), $option->offsetGet('title')), 'level' => 'danger'];
                        }
                    }
                    if ($result['error'] === 1) {
                        return $this->response($result, $product->getUrl(), 'catalog');
                    }
                    $item = new Item([
                        'product_id' => $data['product_id'],
                        'product_name' => $product['name'],
                        'store_id' => $product['store_id'],
                        'qty' => $data['qty'],
                        'is_virtual' => $product->isVirtual() ? 1 : 0,
                        'options' => isset($data['options']) ? (is_string($data['options']) ? json_decode($data['options'], true) : (array) $data['options']) : [],
                        'sku' => $sku,
                        'warehouse_id' => $data['warehouse_id'],
                        'weight' => $product['weight'] * $data['qty'],
                        'base_price' => $product->getFinalPrice($data['qty'], false),
                        'price' => $product->getFinalPrice($data['qty'])
                    ]);
                    $root->getChild('main', true)->setVariable('bulk_item', $item);
                    return $root;
                } catch (ClickFarming $e) {
                    $this->addMessage($this->translate('Click farming check failed.'), 'danger', 'catalog');
                } catch (OutOfStock $e) {
                    $this->addMessage($this->translate('The requested quantity for "%s" is not available.', [(new Product())->load($data['product_id'])['name']]), 'danger', 'catalog');
                } catch (Exception $e) {
                    $this->addMessage($this->translate('An error detected. Please contact us or try again later.'), 'danger', 'catalog');
                    $this->getContainer()->get('log')->logException($e);
                }
            }
        }
        $root->getChild('main', true)->setVariable('bulk', $bulk);
        return $root;
    }

    public function applyPostAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $segment = new Segment('customer');
            $customer = $segment->get('customer');
            $hasLoggedIn = $segment->get('hasLoggedIn');
            $languageId = Bootstrap::getLanguage()->getId();
            $url = $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'];
            if (!$hasLoggedIn) {
                $url = 'customer/account/login/';
                return $this->response($result, $url, 'customer');
            }
            if (empty($data['options_name'])) {
                $data['options_name'] = '';
            }
            if (isset($data['bulk_id']) && $data['bulk_id'] != '') {
                try {
                    $this->beginTransaction();
                    $bulk = new Bulk();
                    $bulk->load($data['bulk_id']);
                    $bulkItemCollection = new itemCollection();
                    $bulkItemCollection->where(['bulk_id' => $data['bulk_id']]);
                    $bulkItemCollection->load(true, true);
                    $product = new Product();
                    $product->load($bulkItemCollection[0]['product_id']);
                    $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
                    $currencyCode = $this->getContainer()->get('request')->getCookie('currency', $baseCurrency);
                    $currency = (new Currency())->load($currencyCode, 'code');

                    $items = [];
                    $items[] = [
                        'product_id' => $bulkItemCollection[0]['product_id'],
                        'product_name' => $bulkItemCollection[0]['product_name'],
                        'options' => $bulkItemCollection[0]['options'],
                        'options_name' => $bulkItemCollection[0]['options_name'],
                        'options_image' => $bulkItemCollection[0]['options_image'],
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
                        'customer_id' => $customer['id'],
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
                    $segment = new Segment('checkout');
                    $segment->set('hasNewOrder', 1);
                } catch (ClickFarming $e) {
                    $this->rollback();
                    $this->addMessage($this->translate('Click farming check failed.'), 'danger', 'catalog');
                    $result['redirect'] = $url;
                } catch (OutOfStock $e) {
                    $this->rollback();
                    $this->addMessage($this->translate('The requested quantity for "%s" is not available.', [(new Product())->load($bulkItemCollection[0]['product_id'])['name']]), 'danger', 'catalog');
                    $result['redirect'] = $url;
                } catch (Exception $e) {
                    $this->rollback();
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                    $this->getContainer()->get('log')->logException($e);
                }
            } else {
                $result = $this->validateForm($data, ['size', 'product_id', 'qty', 'warehouse_id']);
                $files = $this->getRequest()->getUploadedFile();
                if ($files) {
                    $path = BP . 'pub/upload/bulk/';
                    if (!is_dir($path)) {
                        mkdir($path, 0777, true);
                    }
                    foreach ($files['image'] as $file) {
                        if ($file->getError() === UPLOAD_ERR_OK && in_array($file->getClientMediaType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                            $newName = $file->getClientFilename();
                            while (file_exists($path . $newName)) {
                                $newName = preg_replace('/(\.[^\.]+$)/', random_int(0, 9) . '$1', $newName);
                                if (strlen($newName) >= 120) {
                                    throw new Exception('The file is existed.');
                                }
                            }
                            $file->moveTo($path . $newName);
                            $data['description'] .= '<img src="' . $this->getBaseUrl('pub/upload/bulk/' . $newName) . '" />';
                        }
                    }
                }

                if ($result['error'] === 0) {
                    try {
                        $this->beginTransaction();
                        if (!empty($data['options']) && is_string($data['options'])) {
                            $options = @json_decode($data['options'], true);
                            if (!empty($options)) {
                                $data['options'] = $options;
                            }
                        }
                        $product = new Product();
                        $options = $product->load($data['product_id'])->getOptions(['is_required' => 1]);
                        $sku = $product['sku'];
                        //check require options
                        foreach ($options as $option) {
                            if (!isset($data['options'][$option->getId()])) {
                                $result['error'] = 1;
                                $result['redirect'] = $product->getUrl();
                                $result['message'][] = ['message' => sprintf($this->translate('The %s field is required and cannot be empty.'), $option->offsetGet('title')), 'level' => 'danger'];
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
                        if ($result['error'] === 1) {
                            return $this->response($result, $product->getUrl(), 'catalog');
                        }
                        $isVirtual = $product->isVirtual() ? 1 : 0;
                        $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
                        $currencyCode = $this->getContainer()->get('request')->getCookie('currency', $baseCurrency);
                        $currency = (new Currency())->load($currencyCode, 'code');
                        $items = [];
                        $items[] = [
                            'product_id' => $data['product_id'],
                            'product_name' => $product['name'],
                            'options' => is_string($data['options']) ? $data['options'] : json_encode($data['options']),
                            'options_name' => $data['options_name'],
                            'options_image' => $data['options_image'],
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
                        $bulk = new Bulk([
                            'customer_id' => $customer['id'],
                            'customer_name' => $customer['username'],
                            'size' => $data['size'],
                            'count' => 0,
                            'description' => $data['description']
                        ]);
                        $bulk->save();
                        $discount_detail = ['Bulk' => ['id' => $bulk->getId(), 'detail' => $bulk]];
                        $bulkItem = new Item([
                            'bulk_id' => $bulk->getId(),
                            'product_id' => $data['product_id'],
                            'product_name' => $product['name'],
                            'options' => is_string($data['options']) ? $data['options'] : json_encode($data['options']),
                            'options_name' => $data['options_name'],
                            'options_image' => $data['options_image'],
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
                            'customer_id' => $customer['id']
                        ]);
                        $bulkItem->save();
                        $key = $data['warehouse_id'] . '-' . $product['store_id'];
                        $orders = [];
                        $additional = [];
                        $additional['bulk'] = $bulk->getId();
                        $orderArray = [
                            'status_id' => $paymentMethod->getNewOrderStatus(),
                            'customer_id' => $customer['id'],
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
                        $segment = new Segment('checkout');
                        $segment->set('hasNewOrder', 1);
                    } catch (ClickFarming $e) {
                        $this->rollback();
                        $this->addMessage($this->translate('Click farming check failed.'), 'danger', 'catalog');
                        $result['redirect'] = $url;
                    } catch (OutOfStock $e) {
                        $this->rollback();
                        $this->addMessage($this->translate('The requested quantity for "%s" is not available.', [(new Product())->load($data['product_id'])['name']]), 'danger', 'catalog');
                        $result['redirect'] = $url;
                    } catch (Exception $e) {
                        $this->rollback();
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                        $this->getContainer()->get('log')->logException($e);
                    }
                }
            }
            return $this->response($result, $url, 'checkout');
        }
    }

    public function retreatAction()
    {
        $id = $this->getRequest()->getQuery('bulk');
        $url = $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'];
        $result = ['error' => 0, 'message' => []];
        try {
            $bulk = new Bulk();
            $bulk->load($id);
            $segment = new Segment('customer');
            $customer = $segment->get('customer')['id'];
            if (!$bulk->hasMember($customer)) {
                return $this->redirectReferer();
            }
            $id = $bulk->getOrderId();
            if ($this->refund([$id])) {
                $this->createCreditMemo([$id]);
                $bulk->delMember($customer);
                $result['message'][] = ['message' => $this->translate('You have retreated successfully.'), 'level' => 'success'];
            } else {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('You have not completed the payment yet.'), 'level' => 'danger'];
            }
        } catch (Exception $e) {
            $result['error'] = 1;
            $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
            $this->getContainer()->get('log')->logException($e);
        }
        return $this->response($result, $url, 'checkout');
    }

    public function cancelAction()
    {
        $data = $this->getRequest()->isPost() ? $this->getRequest()->getPost() : $this->getRequest()->getQuery();
        $this->doCancel();
        if (isset($data['url'])) {
            return $this->redirect(base64_decode($data['url']));
        } else {
            exit();
        }
    }
}
