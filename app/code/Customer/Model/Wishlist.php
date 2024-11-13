<?php

namespace Redseanet\Customer\Model;

use Redseanet\Customer\Model\Collection\Wishlist\Item as Collection;
use Redseanet\Customer\Model\Wishlist\Item as Model;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Catalog\Model\Product;

class Wishlist extends AbstractModel
{
    protected function construct()
    {
        $this->init('wishlist', 'id', ['id', 'customer_id']);
    }

    public function getItems()
    {
        if ($this->getId()) {
            $items = new Collection();
            $items->where(['wishlist_id' => $this->getId()]);
            return $items;
        }
        return [];
    }

    public function addItem($data)
    {
        $item = new Model($data);
        $product = new Product();
        $product->load($data['product_id'], 'id');
        if (!empty($product['store_id'])) {
            $data['qty'] = !empty($data['qty']) ? $data['qty'] : 1;
            $item->setData([
                'id' => null,
                'wishlist_id' => $this->getId(),
                'store_id' => $data['store_id'] ?? $product['store_id'],
                'product_name' => $data['product_name'] ?? $product['name'],
                'description' => !empty($product['description']) ? preg_replace('/\<[^\>]+\>/', '', $product['description']) : '',
                'price' => $data['base_price'] ?? $product->getFinalPrice($data['qty'], false),
                'options' => isset($data['options']) ? (is_scalar($data['options']) ? $data['options'] : json_encode($data['options'])) : null,
                'options_name' => isset($data['options_name']) ? (is_scalar($data['options_name']) ? $data['options_name'] : json_encode($data['options_name'])) : null,
                'image' => (isset($data['image']) && $data['image'] != '') ? $data['image'] : null,
                'qty' => $data['qty'],
                'product_id' => $data['product_id']
            ]);
            $item->save();
        }
        $this->flushList('wishlist');
        return $this;
    }
}
