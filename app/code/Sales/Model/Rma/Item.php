<?php

namespace Redseanet\Sales\Model\Rma;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Model\AbstractModel;

class Item extends AbstractModel
{
    protected $product = null;

    protected function construct()
    {
        $this->init('sales_rma_item', 'id', [
            'id', 'item_id', 'rma_id', 'qty'
        ]);
    }

    public function &offsetGet(mixed $key): mixed
    {
        $result = parent::offsetGet($key);
        if (!$result) {
            if ($key === 'product' && !empty($this->storage['product_id'])) {
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
