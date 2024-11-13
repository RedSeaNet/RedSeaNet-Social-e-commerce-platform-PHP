<?php

namespace Redseanet\Bulk\Model\Bulk;

use Redseanet\Catalog\Listeners\Inventory;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Sales\Model\Cart;
use Redseanet\Sales\Model\Cart\Item as CartItem;
use Redseanet\Retailer\Listeners\Farm;

class Item extends AbstractModel
{
    protected $product = null;
    protected static $inventory = null;
    protected static $farm = null;

    protected function construct()
    {
        $this->init('bulk_sale_item', 'id', [
            'id', 'bulk_id', 'product_id', 'product_name', 'options', 'options_name', 'options_image', 'qty',
            'sku', 'is_virtual', 'free_shipping', 'base_price', 'price',
            'base_discount', 'discount', 'base_tax', 'tax', 'base_total',
            'total', 'weight', 'store_id', 'warehouse_id'
        ]);
    }

    public function &offsetGet($key): mixed
    {
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
}
