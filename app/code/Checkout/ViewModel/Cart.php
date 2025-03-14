<?php

namespace Redseanet\Checkout\ViewModel;

use Redseanet\Catalog\Model\Warehouse;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Sales\Model\Cart as CartSingleton;

class Cart extends Template
{
    protected static $cart = null;
    protected static $currency = null;
    protected static $qty = null;
    protected $warehouses = [];
    protected $outOfStock = [];

    public function getCart()
    {
        if (is_null(self::$cart)) {
            self::$cart = CartSingleton::instance();
        }
        return self::$cart;
    }

    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }

    public function getQty($withDisabled = false)
    {
        if (is_null(self::$qty)) {
            self::$qty = $this->getCart()->getQty(null, $withDisabled);
        }
        return self::$qty;
    }

    public function canSold($item)
    {
        if (isset($this->outOfStock[$item['id']])) {
            return false;
        }
        if (!isset($this->warehouses[$item['warehouse_id']])) {
            $this->warehouses[$item['warehouse_id']] = new Warehouse();
            $this->warehouses[$item['warehouse_id']]->load($item['warehouse_id']);
        }
        $product = $item['product'];
        $inventory = $this->warehouses[$item['warehouse_id']]->getInventory($product->getId(), $item['option_value_id_string']);
        if ($product->canSold() && isset($inventory['status']) && $inventory['status'] &&
                $inventory['qty'] > $inventory['reserve_qty'] &&
                min((float) $inventory['max_qty'], (float) $inventory['qty']) > (float) $inventory['min_qty']) {
            return true;
        }
        $this->outOfStock[$item['id']] = 1;
        return false;
    }

    public function getItems()
    {
        $items = $this->getCart()->getItems();
        $result = [];
        foreach ($items as $item) {
            if (!$this->canSold($item)) {
                $item['disabled'] = true;
            } else {
                $options = json_decode($item['options'], true);
                foreach ($item['product']->getOptions() as $option) {
                    if ($option['is_required'] && !isset($options[$option->getId()])) {
                        $item['disabled'] = true;
                    }
                }
            }
            $result[] = $item;
        }
        usort($result, function ($a, $b) {
            return $a['store_id'] <=> $b['store_id'];
        });
        return $result;
    }

    public function getRow($item)
    {
        $row = $this->getChild('item');
        $row->setVariable('item', $item);
        return $row;
    }
}
