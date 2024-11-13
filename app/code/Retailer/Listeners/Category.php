<?php

namespace Redseanet\Retailer\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;

class Category implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    public function afterSave($event)
    {
        $product = $event['model'];
        if (!empty($product->offsetGet('store_category'))) {
            $tableGateway = $this->getTableGateway('retailer_category_with_product');
            $tableGateway->delete(['product_id' => $product->getId()]);
            foreach ((array) $product->offsetGet('store_category') as $categoryId) {
                $tableGateway->insert(['product_id' => $product->getId(), 'category_id' => $categoryId]);
            }
        }
    }

    public function afterLoad($event)
    {
        $product = $event['model'];
        if (empty($product->offsetGet('store_category'))) {
            $tableGateway = $this->getTableGateway('retailer_category_with_product');
            $result = $tableGateway->select(['product_id' => $product->getId()])->toArray();
            $ids = [];
            foreach ($result as $item) {
                $ids[] = $item['category_id'];
            }
            $product->offsetSet('store_category', $ids);
        }
    }
}
