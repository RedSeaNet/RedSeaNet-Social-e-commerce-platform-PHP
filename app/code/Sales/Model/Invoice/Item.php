<?php

namespace Redseanet\Sales\Model\Invoice;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Sales\Model\Invoice;
use Redseanet\Sales\Model\Order\Item as OrderItem;

class Item extends AbstractModel
{
    protected $product = null;

    protected function construct()
    {
        $this->init('sales_order_invoice_item', 'id', [
            'id', 'item_id', 'invoice_id', 'product_id', 'product_name',
            'options', 'qty', 'sku', 'base_price', 'price', 'base_discount',
            'discount', 'base_tax', 'tax', 'base_total', 'total'
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

    public function getInvoice()
    {
        if (isset($this->storage['invoice_id'])) {
            return (new Invoice())->load($this->storage['invoice_id']);
        }
        return null;
    }

    public function collateTotals()
    {
        $item = new OrderItem();
        $item->load($this->storage['item_id']);
        $currency = $this->getInvoice()->getCurrency();
        $this->setData('base_price', $item['base_price']);
        $this->setData('price', $currency->convert($this->storage['base_price']));
        $this->setData('base_discount', (float) $item['base_discount'] * $this->storage['qty'] / (float) $item['qty']);
        $this->setData('discount', $currency->convert($this->storage['base_discount']));
        $this->setData('base_tax', (float) $item['base_tax'] * $this->storage['qty'] / (float) $item['qty']);
        $this->setData('tax', $currency->convert($this->storage['tax']));
        $this->setData('base_total', $this->storage['base_price'] * $this->storage['qty'] + $this->storage['base_tax'] + $this->storage['base_discount']);
        $this->setData('total', $this->storage['price'] * $this->storage['qty'] + $this->storage['tax'] + $this->storage['discount']);
        return $this;
    }
}
