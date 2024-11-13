<?php

namespace Redseanet\Sales\Model;

use Exception;
use Redseanet\Catalog\Model\Product;
use Redseanet\Catalog\Model\Product\Option;
use Redseanet\Customer\Model\Address;
use Redseanet\I18n\Model\Currency;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Stdlib\Singleton;
use Redseanet\Sales\Model\Cart\Item;
use Redseanet\Sales\Model\Collection\Cart as Collection;
use Redseanet\Sales\Model\Collection\Cart\Item as ItemCollection;

final class Cart extends AbstractModel implements Singleton
{
    use \Redseanet\Log\Traits\Ip;

    protected static $instance = null;
    protected $items = null;
    protected $additional = null;

    protected function construct()
    {
        $this->init('sales_cart', 'id', [
            'id', 'customer_id', 'status', 'additional', 'customer_note', 'discount_detail',
            'billing_address_id', 'shipping_address_id', 'billing_address', 'shipping_address',
            'is_virtual', 'free_shipping', 'base_currency', 'currency', 'base_subtotal',
            'shipping_method', 'payment_method', 'base_shipping', 'shipping', 'subtotal',
            'base_discount', 'discount', 'base_tax', 'tax', 'base_total', 'total', 'coupon', 'ip'
        ]);
    }

    public function initInstance()
    {
        $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
        $currency = $this->getContainer()->get('request')->getCookie('currency', $baseCurrency);
        $segment = new Segment('customer');
        if ($segment->get('cart')) {
            $this->load($segment->get('cart'));
        } elseif ($segment->get('hasLoggedIn')) {
            $collection = new Collection();
            $customer = $segment->get('customer');
            $collection->where([
                'customer_id' => $customer['id'],
                'status' => 1
            ])->order('id DESC');
            $collection->load(true, true);
            if ($collection->count()) {
                $this->setData($collection->toArray()[0]);
            } else {
                $this->regenerate();
            }
        }
        if ($this->getId()) {
            if ($this->storage['base_currency'] !== $baseCurrency) {
                $this->convertBasePrice($baseCurrency, $this->storage['base_currency']);
            }
            if ($this->storage['currency'] !== $currency) {
                $this->convertPrice($currency, $this->storage['currency']);
            }
        }
    }

    /**
     * @return Cart
     */
    public static function instance()
    {
        if (is_null(static::$instance) || !static::$instance['status']) {
            static::$instance = new static();
            static::$instance->initInstance();
        }
        return static::$instance;
    }

    public function abandon()
    {
        $items = $this->getItems(true);
        $result = [];
        foreach ($items as $item) {
            if ($item['status']) {
                $result[] = $item->toArray();
                $this->removeItem($item);
            }
        }
        if (count($items)) {
            $this->storage['additional'] = '';
            $this->collateTotals();
        } else {
            if (!empty($this->storage['status'])) {
                $this->setData('status', 0)->save();
            }
            $segment = new Segment('customer');
            $segment->offsetUnset('cart');
            static::$instance = null;
        }
        return $result;
    }

    public function combine($cart)
    {
        $id = $this->getId();
        try {
            $this->beginTransaction();
            foreach ($cart->getItems() as $item) {
                $this->addItem($item['product_id'], $item['qty'], $item['warehouse_id'], json_decode($item['options'], true), $item['sku'], false);
            }
            $cart->setData('base_currency', $this->getContainer()->get('config')['i18n/currency/base']);
            $cart->setData('currency', $this->getContainer()->get('config')['i18n/currency/base']);
            $cart->setData('status', 0)->save();
            $this->collateTotals();
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            $this->getContainer()->get('log')->logException($e);
        }
        $segment = new Segment('customer');
        $segment->set('cart', $id);
        static::$instance = $this;
    }

    public function regenerate($cart = null, $currency = null, $baseCurrency = null)
    {
        $segment = new Segment('customer');
        if (is_null($baseCurrency)) {
            $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
        }
        if (is_null($currency)) {
            $currency = $this->getContainer()->get('request')->getCookie('currency', $baseCurrency);
        }
        if (is_null($cart)) {
            $cart = new static();
        }
        $cart->setData([
            'base_currency' => $baseCurrency,
            'currency' => $currency
        ]);
        if ($segment->get('hasLoggedIn')) {
            $cart->setData([
                'customer_id' => $segment->get('customer')['id']
            ]);
        }
        $cart->save();
        $segment->set('cart', $cart->getId());
        if (!$this->getId()) {
            static::$instance = $cart;
            $this->setData($cart->toArray());
        }
        return $cart;
    }

    public function getItem($id)
    {
        if (!$this->getId()) {
            return null;
        }
        if (is_null($this->items)) {
            $this->getItems();
        }
        if (isset($this->items[$id])) {
            return $this->items[$id];
        } else {
            $items = new ItemCollection();
            $items->where(['cart_id' => $this->getId(), 'id' => $id])->order('store_id, warehouse_id');
            if ($items->count()) {
                return $items[0];
            }
        }
        return null;
    }

    public function getItems($force = false)
    {
        if (!$this->getId()) {
            return [];
        }
        if ($force || is_null($this->items)) {
            $items = new ItemCollection();
            $items->where(['cart_id' => $this->getId()]);
            $result = [];
            $items->walk(function ($item) use (&$result) {
                $result[$item['id']] = $item;
            });
            $this->items = $result;
            if ($force) {
                return clone $items;
            }
        }
        return $this->items;
    }

    public function getAdditional($key = null)
    {
        if (is_null($this->additional)) {
            $this->additional = empty($this->storage['additional']) ? [] : json_decode($this->storage['additional'], true);
        }
        return $key ? ($this->additional[$key] ?? '') : $this->additional;
    }

    public function isVirtual($storeId = null)
    {
        foreach ($this->getItems() as $item) {
            if ($item['status'] && (is_null($storeId) || $item['store_id'] == $storeId) && !$item['is_virtual']) {
                return false;
            }
        }
        return true;
    }

    public function addItem($productId, $qty, $warehouseId, array $options = [], $sku = '', $collate = true, $languageId = '', $optionName = '', $image = '')
    {
        if (!$this->getId()) {
            $this->regenerate();
        }
        if ($languageId != '') {
            $product = new Product($languageId);
        } else {
            $product = new Product();
        }
        $product->load($productId);
        ksort($options);
        if (!$sku) {
            $sku = $product['sku'];
            foreach ($options as $key => $value) {
                $option = new Option();
                $option->load($key);
                if (in_array($option->offsetGet('input'), ['select', 'radio', 'checkbox', 'multiselect'])) {
                    $value = $option->getValue($value, false);
                    if (isset($value['sku']) && $value['sku'] !== '') {
                        $sku .= '-' . $option->getValue($value, false)['sku'];
                    }
                } elseif ($value !== '' && $option['sku'] !== '') {
                    $sku .= '-' . $option['sku'];
                }
            }
        }
        $this->getEventDispatcher()->trigger('cart.add.before', [
            'product_id' => $productId,
            'product' => $product,
            'qty' => $qty,
            'warehouse_id' => $warehouseId,
            'sku' => $sku,
            'options' => $options,
            'image' => $image
        ]);

        $items = new ItemCollection();
        $items->where([
            'cart_id' => $this->getId(),
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'store_id' => $product['store_id'],
            'sku' => $sku,
            'options' => json_encode($options)
        ]);
        $item = new Item();
        if ($items->count()) {
            $newQty = $items[0]['qty'] + $qty;
            $item->setData($items[0]->toArray())
                    ->setData([
                        'qty' => $newQty,
                        'base_price' => $product->getFinalPrice($newQty, false),
                        'price' => $product->getFinalPrice($newQty)
                    ])->collateTotals()->save();
        } else {
            $item->setData([
                'cart_id' => $this->getId(),
                'product_id' => $productId,
                'product_name' => $product['name'],
                'store_id' => $product['store_id'],
                'qty' => $qty,
                'is_virtual' => $product->isVirtual() ? 1 : 0,
                'options' => json_encode($options),
                'options_name' => $optionName,
                'sku' => $sku,
                'warehouse_id' => $warehouseId,
                'weight' => $product['weight'] * $qty,
                'base_price' => $product->getFinalPrice($qty, false),
                'price' => $product->getFinalPrice($qty),
                'image' => $image
            ])->collateTotals()->save();
        }
        $this->items[$item->getId()] = $item->toArray();
        $this->getEventDispatcher()->trigger('cart.add.after', [
            'product_id' => $productId,
            'qty' => $qty,
            'warehouse_id' => $warehouseId,
            'sku' => $sku,
            'options' => $options,
            'item' => $item
        ]);
        if ($collate) {
            $this->collateTotals();
        }
        $this->flushList('sales_cart');
        $this->flushList('sales_cart_item');

        return $this;
    }

    public function changeQty($item, $qty, $collate = true)
    {
        if (is_numeric($item)) {
            $item = (new Item())->load($item);
        }
        $inventory = $item['product']->getInventory($item['warehouse_id'], $item['sku']);
        if ($item['qty'] > $qty && $inventory['min_qty'] <= $qty || $item['qty'] < $qty && $inventory['max_qty'] >= $qty) {
            $this->getEventDispatcher()->trigger('cart.add.before', [
                'product_id' => $item['product_id'],
                'qty' => $qty,
                'warehouse_id' => $item['warehouse_id'],
                'sku' => $item['sku'],
                'options' => $item['options']
            ]);
            $item->setData(['qty' => $qty, 'status' => 1])->collateTotals()->save();
            $this->items[$item->getId()] = $item->toArray();
        } elseif ($item['qty'] == $qty && $item['status'] == 0) {
            return $this->changeItemStatus($item, true, $collate);
        }
        if ($collate) {
            $this->collateTotals();
        }
        return $this;
    }

    public function changeItemStatus($item, $status, $collate = true)
    {
        if (is_numeric($item)) {
            $item = (new Item())->load($item);
        }
        $item->setData(['status' => $status])->collateTotals()->save();
        $this->items[$item->getId()]['status'] = $status;
        if ($collate) {
            $this->collateTotals();
        }
        return $this;
    }

    public function removeItem($item)
    {
        if (is_numeric($item)) {
            unset($this->items[$item]);
            $item = (new Item())->setData('id', $item);
        } else {
            unset($this->items[$item['id']]);
        }
        $item->remove();
        $this->getContainer()->get('eventDispatcher')->trigger('cart.item.remove.after', ['model' => $this]);
        $this->collateTotals();
        return $this;
    }

    public function removeItems($items)
    {
        if (is_array($items) || $items instanceof \Traversable) {
            foreach ($items as $item) {
                if (is_numeric($item)) {
                    unset($this->items[$item]);
                    $item = (new Item())->load($item);
                } else {
                    unset($this->items[$item['id']]);
                }
                $item->remove();
            }
            $this->getContainer()->get('eventDispatcher')->trigger('cart.item.remove.after', ['model' => $this]);
            $this->collateTotals();
        }
        return $this;
    }

    public function removeAllItems()
    {
        foreach ($this->getItems() as $item) {
            $item = new Item();
            $item->setId($item['id'])->remove();
        }
        $this->items = [];
        $this->getContainer()->get('eventDispatcher')->trigger('cart.item.remove.after', ['model' => $this]);
        $this->collateTotals();
        return $this;
    }

    public function convertPrice($to, $from = null)
    {
        try {
            $this->beginTransaction();
            if (is_null($from)) {
                $from = (new Currency())->load($this->offsetGet('currency'), 'code');
            } elseif (is_string($from)) {
                $from = (new Currency())->load($from, 'code');
            }
            if (is_string($to)) {
                $to = (new Currency())->load($to, 'code');
            }
            foreach ($this->getItems() as $item) {
                $item = new Item($item);
                foreach (['price', 'tax', 'discount', 'total'] as $attr) {
                    $item->setData($attr, $to->convert($from->rconvert($item->offsetGet('base_' . $attr))));
                }
                $item->save();
            }
            foreach (['subtotal', 'shipping', 'tax', 'discount', 'total'] as $attr) {
                $this->setData($attr, $to->convert($from->rconvert($this->storage['base_' . $attr] ?? 0)));
            }
            $this->setData('currency', $to->offsetGet('code'))->save();
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            $this->getContainer()->get('log')->logException($e);
        }
    }

    public function convertBasePrice($to, $from = null)
    {
        try {
            $this->beginTransaction();
            if (is_null($from)) {
                $from = (new Currency())->load($this->offsetGet('currency'), 'code');
            } elseif (is_string($from)) {
                $from = (new Currency())->load($from, 'code');
            }
            if (is_string($to)) {
                $to = (new Currency())->load($to, 'code');
            }
            foreach ($this->getItems() as $item) {
                $item = new Item($item);
                foreach (['base_price', 'base_tax', 'base_discount', 'base_total'] as $attr) {
                    $item->offsetSet($attr, $to->convert($from->rconvert($item->offsetGet($attr))));
                }
                $item->save();
            }
            foreach (['base_subtotal', 'base_shipping', 'base_tax', 'base_discount', 'base_total'] as $attr) {
                $this->offsetSet($attr, $to->convert($from->rconvert($this->storage[$attr] ?? 0)));
            }
            $this->offsetSet('base_currency', $to->offsetGet('code'));
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            $this->getContainer()->get('log')->logException($e);
        }
    }

    public function collateTotals()
    {
        if (!$this->getId()) {
            $this->regenerate();
        }
        $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];
        $currency = (new Currency())->load($this->getContainer()->get('request')->getCookie('currency', $baseCurrency));
        $items = $this->getItems(true);
        $items->load(false);
        $baseSubtotal = 0;
        $storeId = [];
        foreach ($items as $item) {
            if ($item->offsetGet('status')) {
                $baseSubtotal += $item->collateTotals()->offsetGet('base_total');
                if (!isset($storeId[$item['store_id']])) {
                    $storeId[$item['store_id']] = [];
                }
                $storeId[$item['store_id']][] = $item;
            }
        }
        $shipping = 0;
        if (!$this->offsetGet('free_shipping') && !$this->offsetGet('is_virtual')) {
            foreach ($storeId as $id => $i) {
                if ($method = $this->getShippingMethod($id)) {
                    $shipping += $method->getShippingRate($i, $this->getShippingMethod($id, true), $this);
                }
            }
        }
        $this->setData([
            'base_subtotal' => $baseSubtotal,
            'base_shipping' => $shipping,
            'base_discount' => 0,
            'discount' => 0,
            'discount_detail' => '',
            'base_tax' => 0,
            'tax' => 0
        ])->setData([
            'subtotal' => $currency->convert($this->storage['base_subtotal']),
            'shipping' => $currency->convert($shipping),
        ]);
        $this->getEventDispatcher()->trigger('tax.calc', ['model' => $this]);
        $this->getEventDispatcher()->trigger('promotion.calc', ['model' => $this]);
        $this->setData([
            'base_total' => $this->storage['base_subtotal'] +
            $this->storage['base_shipping'] +
            ($this->storage['base_tax'] ?? 0) +
            ($this->storage['base_discount'] ?? 0),
            'total' => $this->storage['subtotal'] +
            $this->storage['shipping'] +
            ($this->storage['tax'] ?? 0) +
            ($this->storage['discount'] ?? 0)
        ]);
        $this->save();
        return $this;
    }

    public function getShippingAddress()
    {
        if (isset($this->storage['shipping_address_id'])) {
            $address = (new Address())->load($this->storage['shipping_address_id']);
            return $address->getId() ? $address : null;
        }
        return null;
    }

    public function getBillingAddress()
    {
        if (isset($this->storage['billing_address_id'])) {
            $address = (new Address())->load($this->storage['billing_address_id']);
            return $address->getId() ? $address : null;
        }
        return null;
    }

    public function getQty($storeId = null, $withDisabled = false)
    {
        $qty = 0;
        foreach ($this->getItems() as $item) {
            if ((is_null($storeId) || $item->offsetGet('store_id') == $storeId) && ($withDisabled || $item['status'])) {
                $qty += $item['qty'];
            }
        }
        return $qty;
    }

    public function getWeight($storeId = null)
    {
        $weight = 0;
        foreach ($this->getItems() as $item) {
            if (is_null($storeId) || $item->offsetGet('store_id') == $storeId) {
                $weight += $item['weight'];
            }
        }
        return $weight;
    }

    public function getCoupon($storeId = null)
    {
        if (!empty($this->storage['coupon'])) {
            $coupons = json_decode($this->storage['coupon'], true);
            return !is_null($storeId) && isset($coupons[$storeId]) ? $coupons[$storeId] : $coupons;
        }
        return '';
    }

    public function getShippingMethod($storeId, $nameOnly = false)
    {
        if (isset($this->storage['shipping_method'])) {
            $methods = json_decode($this->storage['shipping_method'], true);
            if (isset($methods[$storeId])) {
                $className = $this->getContainer()->get('config')['shipping/' . preg_replace('/:[^:]+$/', '', $methods[$storeId]) . '/model'];
                return $nameOnly ? $methods[$storeId] : new $className();
            }
        }
        return null;
    }

    public function getPaymentMethod()
    {
        if (isset($this->storage['payment_method'])) {
            $className = $this->getContainer()->get('config')['payment/' . $this->storage['payment_method'] . '/model'];
            return new $className();
        }
        return null;
    }

    public function getCurrency()
    {
        if (isset($this->storage['currency'])) {
            return (new Currency())->load($this->storage['currency'], 'code');
        }
        return $this->getContainer()->get('currency');
    }

    public function getStoreTotal($storeId)
    {
        $items = $this->getItems(true);
        $items->load(false);
        $total = 0;
        foreach ($items as $item) {
            if ($item->offsetGet('status')) {
                if ($item['store_id'] == $storeId) {
                    $total += $item['base_total'];
                }
            }
        }
        return $total;
    }

    protected function beforeSave()
    {
        parent::beforeSave();
        $this->storage['ip'] = $this->getRealIp();
    }
}
