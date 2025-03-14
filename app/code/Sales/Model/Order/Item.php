<?php

namespace Redseanet\Sales\Model\Order;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Sales\Model\Order;

class Item extends AbstractModel {

    protected $product = null;

    protected function construct() {
        $this->init('sales_order_item', 'id', [
            'id', 'order_id', 'product_id', 'product_name', 'options', 'options_name', 'option_value_id', 'option_value_id_string', 'image', 'qty',
            'sku', 'is_virtual', 'free_shipping', 'base_price', 'price',
            'base_discount', 'discount', 'base_tax', 'tax', 'base_total',
            'total', 'weight'
        ]);
    }

    public function getOptions() {
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

    public function &offsetGet($key): mixed {
        $result = parent::offsetGet($key);
        if (!$result) {
            if ($key === 'product') {
                if (is_null($this->product)) {
                    $this->product = new Product();
                    $this->product->load($this->storage['product_id']);
                }
                $result = $this->product;
            }
        }
        return $result;
    }

    public function getOrder() {
        if (isset($this->storage['order_id'])) {
            return (new Order())->load($this->storage['order_id']);
        }
        return null;
    }

    public function collateTotals() {
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
        $this->setData('base_price', max(0, $basePrice + $sum));
        $this->setData('price', $this->getOrder()->getCurrency()->convert($this->storage['base_price']));
        $this->setData('base_total', $this->storage['base_price'] * $this->storage['qty'] + ($this->storage['base_tax'] ?? 0) + ($this->storage['base_discount'] ?? 0));
        $this->setData('total', $this->storage['price'] * $this->storage['qty'] + ($this->storage['tax'] ?? 0) + ($this->storage['discount'] ?? 0));
        return $this;
    }

}
