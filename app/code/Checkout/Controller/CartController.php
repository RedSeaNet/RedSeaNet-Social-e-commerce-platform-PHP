<?php

namespace Redseanet\Checkout\Controller;

use Exception;
use Redseanet\Catalog\Exception\OutOfStock;
use Redseanet\Catalog\Model\Product;
use Redseanet\Customer\Model\Wishlist;
use Redseanet\Checkout\ViewModel\Cart\Item;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Exception\ClickFarming;
use Redseanet\Sales\Model\Cart;
use Redseanet\Lib\Bootstrap;
use Redseanet\Resource\Model\Resource;

class CartController extends ActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;

    public function addAction()
    {
        $languageId = Bootstrap::getLanguage()->getId();
        $data = $this->getRequest()->isGet() ? $this->getRequest()->getQuery() : $this->getRequest()->getPost();
        $result = $this->validateForm($data, ['product_id', 'qty', 'warehouse_id']);
        if ($result['error'] === 0) {
            try {
                if (!empty($data['options']) && is_string($data['options'])) {
                    $options = @json_decode($data['options'], true);
                    if (!empty($options)) {
                        $data['options'] = $options;
                    }
                }
                $product = new Product($languageId);
                $product->load($data['product_id']);
                $options = $product->getOptions(['is_required' => 1]);
                foreach ($options as $option) {
                    if (!isset($data['options'][$option->getId()])) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => sprintf($this->translate('The %s field is required and cannot be empty.'), $option->offsetGet('title')), 'level' => 'danger'];
                    }
                }
                if ($result['error'] === 1) {
                    return $this->response($result, $product->getUrl(), 'checkout');
                }
                if (!isset($data['image']) || $data['image'] == '') {
                    $data['image'] = '';
                    if (isset($data['options']) && is_array($data['options']) && count($data['options']) > 0) {
                        $images = $product['images'];
                        if ($images) {
                            foreach ($data['options'] as $id => $value) {
                                $value = $product->getOption($id, $value, $languageId);
                                foreach ($images as $image) {
                                    if ($image['group'] == $value) {
                                        $data['image'] = $image['name'];
                                    }
                                }
                            }
                        }
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
                            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => ['title' => 'The ' . $option->offsetGet('title') . ' field is required and cannot be empty.', 'content' => 'The ' . $option->offsetGet('title') . ' field is required and cannot be empty.', 'level' => 'danger']];

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
                Cart::instance()->addItem($data['product_id'], $data['qty'], $data['warehouse_id'], isset($data['options']) ?
                                (is_string($data['options']) ? json_decode($data['options'], true) : (array) $data['options']) : [], $data['sku'] ?? '', true, $languageId, implode(',', $productOptionsNames), $data['image']);

                if ($this->getRequest()->isXmlHttpRequest()) {
                    $result['html'] = (new Item())->setTemplate('checkout/minicart/item')->setVariable('item', new Cart\Item($data))->__toString();
                }
                $this->flushList('sales_cart_item');
                $this->flushList('sales_cart');
                $result['reload'] = 1;
                $result['message'][] = ['message' => $this->translate('"%s" has been added to your shopping cart.', [(new Product())->load($data['product_id'])['name']]), 'level' => 'success'];
            } catch (ClickFarming $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('Click farming check failed.'), 'content' => $this->translate('Click farming check failed.'), 'level' => 'danger'];
            } catch (OutOfStock $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('The requested quantity for "%s" is not available.', [(new Product())->load($data['product_id'])['name']]), 'level' => 'danger'];
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('Prohibit the purchase of goods sold.'), 'level' => 'danger'];
                $this->getContainer()->get('log')->logException($e);
            }
        }
        return $this->response($result, 'checkout/cart/', 'checkout');
    }

    public function removeAction()
    {
        $data = $this->getRequest()->isGet() ? $this->getRequest()->getQuery() : $this->getRequest()->getPost();
        $result = $this->validateForm($data);
        if ($result['error'] === 0) {
            $cart = Cart::instance();
            try {
                if (isset($data['item'])) {
                    if (is_array($data['item'])) {
                        $cart->removeItems($data['item']);
                        $result['message'][] = ['message' => $this->translate('%d item(s) has been removed from your shopping cart.', [count($data['item'])]), 'level' => 'success'];
                    } else {
                        $item = $cart->getItem($data['item']);
                        if ($item) {
                            $cart->removeItem($data['item']);
                            $result['message'][] = ['message' => $this->translate('"%s" has been removed from your shopping cart.', [$item['product_name']]), 'level' => 'success'];
                        } else {
                            return $this->redirectReferer('checkout/cart/');
                        }
                    }
                } else {
                    $cart->removeAllItems();
                    $result['message'][] = ['message' => $this->translate('All items have been removed from your shopping cart.'), 'level' => 'success'];
                }
                $result['reload'] = 1;
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                $this->getContainer()->get('log')->logException($e);
            }
        }
        return $this->response($result, 'checkout/cart/', 'checkout');
    }

    public function updateAction()
    {
        $data = $this->getRequest()->isGet() ? $this->getRequest()->getQuery() : $this->getRequest()->getPost();
        $result = $this->validateForm($data, ['qty', 'item']);
        if ($result['error'] === 0) {
            $cart = Cart::instance();
            try {
                foreach ($data['qty'] as $id => $qty) {
                    try {
                        if (in_array($id, $data['item'])) {
                            $cart->changeQty($id, $qty, false);
                        } else {
                            $cart->changeItemStatus($id, false, false);
                        }
                    } catch (OutOfStock $e) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('The requested quantity for "%s" is not available.', [$cart->getItem($id)['name']]), 'level' => 'danger'];
                    }
                }
                $cart->setData([
                    'additional' => '',
                    'coupon' => ''
                ]);
                $cart->collateTotals();
                $this->flushList('sales_cart_item');
                $this->flushList('sales_cart');
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                $this->getContainer()->get('log')->logException($e);
            }
        } else {
            return $this->response($result, 'checkout/cart/', 'checkout');
        }
        return $this->response($result, 'checkout/cart/', 'checkout');
    }

    public function indexAction()
    {
        $root = $this->getLayout('checkout_cart');
        if (!count(Cart::instance()->getItems())) {
            $root->addBodyClass('empty-cart');
        }
        return $root;
    }

    public function jsonAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $cart = Cart::instance();
            $result = $cart->collateTotals()->toArray();
            $result['items'] = [];
            foreach ($cart->getItems() as $item) {
                $product = $item['product'];
                $options = $item->offsetGet('options');
                $result['items'][] = $item->toArray() + [
                    '_options' => $options ? $item->getOptions() : [],
                    '_product' => $product->toArray() + [
                        'thumbnail_url' => $product->getThumbnail($options ? json_decode($options, true) : null)
                    ]
                ];
            }
            return $result;
        }
        return $this->notFoundAction();
    }

    public function miniAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->getLayout('checkout_cart_mini');
        }
        return $this->notFoundAction();
    }

    public function moveToWishlistAction()
    {
        $segment = new Segment('customer');
        if (!$segment->get('hasLoggedIn')) {
            $segment->set('afterLogin', 'checkout/cart/');
            return $this->redirect('customer/account/login/');
        }
        $data = $this->getRequest()->isGet() ? $this->getRequest()->getQuery() : $this->getRequest()->getPost();
        $result = $this->validateForm($data, ['item']);
        if ($result['error'] === 0) {
            $wishlist = new Wishlist();
            $wishlist->load($segment->get('customer')['id'], 'customer_id');
            if (!$wishlist->getId()) {
                $wishlist->setData('customer_id', $segment->get('customer')['id'])->save();
            }
            $result['removeLine'] = [];
            try {
                $this->beginTransaction();
                foreach ((array) $data['item'] as $id) {
                    $item = Cart::instance()->getItem($id);
                    if ($item) {
                        $wishlist->addItem($item->toArray());
                        $result['message'][] = ['message' => $this->translate('"%s" has been moved to your wishlist.', [$item['product']['name']], 'checkout'), 'level' => 'success'];
                        $result['removeLine'][] = $item->getId();
                        Cart::instance()->removeItem($item);
                    }
                }
                $this->commit();
            } catch (Exception $e) {
                $this->rollback();
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                $this->getContainer()->get('log')->logException($e);
            }
        }
        return $this->response($result, 'checkout/cart/', 'checkout');
    }
}
