<?php

namespace Redseanet\Bargain\Controller;

use Exception;
use Redseanet\Bargain\Model\Bargain;
use Redseanet\Bargain\Model\BargainCase;
use Redseanet\Bargain\Model\BargainCaseHelp;
use Redseanet\Bargain\Model\Collection\Bargain as bargainCollection;
use Redseanet\Bargain\Model\Collection\BargainCase as bargainCaseCollection;
use Redseanet\Catalog\Exception\OutOfStock;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Exception\ClickFarming;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Item as orderItem;
use Redseanet\Lib\Bootstrap;
use Redseanet\I18n\Model\Currency;

class CheckoutController extends ActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
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

    public function viewAction()
    {
        $result = ['error' => 0, 'message' => []];
        $data = $this->getRequest()->isGet() ? $this->getRequest()->getQuery() : $this->getRequest()->getPost();
        $root = $this->getLayout('bargain_sale_bargain_view');
        $languageId = Bootstrap::getLanguage()->getId();
        if (isset($data['bargain']) && $data['bargain'] != '' && isset($data['bargain_case']) && $data['bargain_case'] != '') {
            $bargainObject = new Bargain();
            $bargainObject->load($data['bargain']);
            if (!$bargainObject->getId() || !$bargainObject['status']) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('The bargain is disable.'), 'level' => 'danger'];
                return $this->response($result, 'bargain/index/list/', 'bargain');
            }
            $bargainCaseObject = new bargainCase();
            $bargainCaseObject->load($data['bargain_case']);
            if (!$bargainCaseObject->getId()) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('The bargain case do not exit.'), 'level' => 'danger'];
                return $this->response($result, 'bargain/bargain/index/?bargain=' . $data['bargain'], 'bargain');
            }
            if ($bargainCaseObject['status'] == 3) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('You have placed an order sidth the bargain case.'), 'level' => 'danger'];
                return $this->response($result, 'bargain/bargain/index/?bargain=' . $data['bargain'], 'bargain');
            } elseif ($bargainCaseObject['status'] == 2) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('The bargain case had cloed.'), 'level' => 'danger'];
                return $this->response($result, 'bargain/bargain/index/?bargain=' . $data['bargain'], 'bargain');
            }
            $product = new Product();
            $product->load($bargainObject['product_id']);
            $item = [
                'product_id' => $bargainObject['product_id'],
                'product_name' => $bargainObject['name'][$languageId],
                'store_id' => $product['store_id'],
                'qty' => 1,
                'is_virtual' => $product->isVirtual() ? 1 : 0,
                'options' => (isset($bargainObject['options']) && $bargainObject['options'] != '' ? json_decode($bargainObject['options'], true) : []),
                'sku' => $bargainObject['sku'],
                'warehouse_id' => $product['warehouse_id'],
                'weight' => $product['weight'],
                'base_price' => $bargainObject['min_price'],
                'price' => $bargainObject['min_price'],
                'product' => $product
            ];
            $root->getChild('main', true)->setVariable('bargain', $bargainObject)->setVariable('bargaincase', $bargainCaseObject)->setVariable('item', $item);
        } else {
            $result['error'] = 1;
            $result['message'][] = ['message' => $this->translate('Bargain id and bargain case id are require.'), 'level' => 'danger'];
            return $this->response($result, 'bargain/index/list/', 'bargain');
        }
        return $root;
    }

    public function placeAction()
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
            $qty = (isset($data['qty']) && $data['qty'] != '' ? intval($data['qty']) : 1);

            try {
                $this->beginTransaction();
                $bargainObject = new Bargain();
                $bargainObject->load($data['bargain']);

                $bargainCaseObject = new BargainCase();
                $bargainCaseObject->load($data['bargain_case']);
                $product = new Product();
                $product->load($bargainObject['product_id']);

                $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
                $currencyCode = $this->getContainer()->get('request')->getCookie('currency', $baseCurrency);
                $currency = (new Currency())->load($currencyCode, 'code');

                $optionNames = [];
                $options = json_decode($bargainObject['options'], true);
                foreach ($product->getOptions() as $option) {
                    $optionNames[] = $option['title'] . ':' . (in_array($option['input'], ['select', 'radio', 'checkbox', 'multiselect']) ?
                            $option->getValue($options[$option->getId()]) : $options[$option->getId()]);
                }
                $optionNamesString = implode(',', $optionNames);

                $items = [];
                $items[] = [
                    'product_id' => $bargainObject['product_id'],
                    'product_name' => $bargainObject['name'][$languageId],
                    'options' => $bargainObject['options'],
                    'options_name' => $optionNamesString,
                    'qty' => $qty,
                    'store_id' => $product['store_id'],
                    'sku' => $bargainObject['sku'],
                    'is_virtual' => $product['is_virtual'],
                    'base_price' => $bargainObject['min_price'],
                    'price' => $currency->convert($bargainObject['min_price']),
                    'base_discount' => 0,
                    'discount' => 0,
                    'base_tax' => 0,
                    'tax' => 0,
                    'base_total' => $bargainObject['min_price'] * $qty,
                    'total' => $currency->convert($bargainObject['min_price'] * $qty),
                    'warehouse_id' => $bargainObject['warehouse_id'],
                    'weight' => $product['weight'] * $qty
                ];

                $billingAddress = $this->validBillingAddress($data);
                $paymentMethod = $this->validPayment(['total' => $bargainObject['min_price']] + $data);
                $base_shipping = 0;
                $shipping_method = $data['shipping_method'][$product['store_id']];
                $customer_note = isset($data['comment']) ? json_encode($data['comment']) : '{}';
                $billing_address_id = '';
                $billing_address = '';
                $shipping_address_id = '';
                $shipping_address = '';
                if ($product['is_virtual']) {
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
                $additional['bargain'] = $bargainObject->getId();
                $additional['bargain_case'] = $bargainCaseObject->getId();
                $discount_detail = ['Bulk' => ['id' => $bargainObject->getId(), 'case_id' => $bargainCaseObject->getId(), 'detail' => $bargainObject]];
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
                    'is_virtual' => $product['is_virtual'],
                    'free_shipping' => '',
                    'base_currency' => $baseCurrency,
                    'currency' => $currencyCode,
                    'base_subtotal' => $bargainObject['min_price'],
                    'shipping_method' => $shipping_method,
                    'payment_method' => $data['payment_method'],
                    'base_shipping' => $base_shipping,
                    'shipping' => $currency->convert($base_shipping),
                    'subtotal' => $currency->convert($bargainObject['min_price'] * $qty),
                    'base_discount' => 0,
                    'discount' => 0,
                    'discount_detail' => json_encode($discount_detail),
                    'base_tax' => 0,
                    'tax' => 0,
                    'base_total' => $bargainObject['min_price'] * $qty + $base_shipping,
                    'total' => $currency->convert($bargainObject['min_price'] * $qty + $base_shipping),
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
                $bargainCaseObject->setData('status', 3);
                $bargainCaseObject->save();
                $bargainObject->setData('stock', ($bargainObject['stock'] - $qty));
                $bargainObject->save();
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

            return $this->response($result, $url, 'checkout');
        }
    }
}
