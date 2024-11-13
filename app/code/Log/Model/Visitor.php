<?php

namespace Redseanet\Log\Model;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Model\AbstractModel;

class Visitor extends AbstractModel
{
    protected function construct()
    {
        $this->init('log_visitor', 'id', ['id', 'customer_id', 'store_id', 'product_id', 'post_id', 'session_id', 'remote_addr', 'http_referer', 'http_user_agent', 'http_accept_charset', 'http_accept_language']);
    }

    public function getProduct()
    {
        if (!empty($this->storage['product_id'])) {
            $product = new Product();
            $product->load($this->storage['product_id']);
            return $product;
        }
        return null;
    }
}
