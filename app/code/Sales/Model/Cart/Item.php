<?php

namespace Redseanet\Sales\Model\Cart;

use Redseanet\Catalog\Model\Product;
use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Sales\Model\Cart;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Store;

class Item extends AbstractModel
{
    use \Redseanet\Log\Traits\Ip;

    protected $product = null;
    protected $store = null;

    protected function construct()
    {
        $this->init('sales_cart_item', 'id', [
            'id', 'cart_id', 'product_id', 'product_name', 'options', 'options_name','option_value_id_string', 'qty',
            'sku', 'is_virtual', 'free_shipping', 'base_price', 'price',
            'base_discount', 'discount', 'base_tax', 'tax', 'base_total',
            'total', 'weight', 'status', 'warehouse_id', 'store_id', 'image', 'ip'
        ]);
    }

    public function getCart()
    {
        if (isset($this->storage['cart_id'])) {
            return (new Cart())->load($this->storage['cart_id']);
        }
        return null;
    }

    public function getOptions()
    {
        $result = [];
        $options = json_decode($this->offsetGet('options'), true);
        foreach ($this->offsetGet('product')->getOptions() as $option) {
            if (isset($options[$option->getId()])) {
                $result[] = [
                    'label' => $option['title'],
                    'value' => (in_array($option['input'], ['select', 'radio', 'checkbox', 'multiselect']) ?
                    $option->getValue($options[$option->getId()]) : $options[$option->getId()])
                ];
            }
        }
        return $result;
    }

    public function &offsetGet($key): mixed
    {
        $result = parent::offsetGet($key);
        if (!$result) {
            if ($key === 'product') {
                if (is_null($this->product) && isset($this->storage['product_id'])) {
                    $this->product = new Product();
                    $this->product->load($this->storage['product_id']);
                }
                $result = $this->product;
            } elseif ($key === 'store') {
                if (is_null($this->store)) {
                    $this->store = new Store();
                    $this->store->load($this->storage['store_id']);
                }
                $result = $this->store;
            }
        }
        return $result;
    }

    public function getInventory()
    {
        $warehouse = new Warehouse();
        return $warehouse->setId($this->storage['warehouse_id'])->getInventory($this->storage['product_id'], $this->storage['option_value_id_string']);
    }

    public function collateTotals()
    {
        $product = $this->offsetGet('product');
        $basePrice = $product->getFinalPrice($this->storage['qty'], false);
        $options = json_decode($this->storage['options'], true);
        $sum = 0;
        if (!empty($options)) {
            foreach ($product->getOptions() as $option) {
                if (isset($options[$option->getId()])) {
                    if (in_array($option->offsetGet('input'), ['select', 'radio', 'checkbox', 'multiselect'])) {
                        foreach ($option->getValues() as $value) {
                            if ($value['id'] == $options[$option->getId()]) {
                                $sum += $value['is_fixed'] ? $value['price'] : $basePrice * $value['price'] / 100;
                            }
                        }
                    } else {
                        $sum += $option['is_fixed'] ? $option['price'] : $basePrice * $option['price'] / 100;
                    }
                }
            }
        }
        $this->setData('base_price', max($basePrice + $sum, 0));
        $this->setData('price', $this->getCart()->getCurrency()->convert($this->storage['base_price']));
        $this->setData('base_total', $this->storage['base_price'] * $this->storage['qty'] + ($this->storage['base_tax'] ?? 0) + ($this->storage['base_discount'] ?? 0));
        $this->setData('total', $this->storage['price'] * $this->storage['qty'] + ($this->storage['tax'] ?? 0) + ($this->storage['discount'] ?? 0));
        return $this;
    }

    protected function beforeSave()
    {
        parent::beforeSave();
        $this->storage['ip'] = $this->getRealIp();
    }
}
