<?php

namespace Redseanet\Sales\Model\Api\Rest;

use Redseanet\Api\Model\Api\Rest\AbstractHandler;
use Redseanet\Oauth\Model\Token;
use Redseanet\Sales\Model\Collection\Order as Collection;

class Order extends AbstractHandler
{
    public function getOrder()
    {
        $data = $this->getRequest()->getQuery();
        $columns = $this->getAttributes('order');
        if (count($columns)) {
            if ($this->authOptions['validation'] > 0) {
                if (isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                    $token = new Token();
                    $token->load($data['openId'], 'open_id');
                    unset($data['openId']);
                    $data['customer_id'] = $token['customer_id'];
                } else {
                    return $this->getResponse()->withStatus(400);
                }
            }
            $collection = new Collection();
            $collection->columns($columns);
            $this->filter($collection, $data);
            $result = [];
            $itemColumns = $this->getAttributes('order_items');
            $collection->walk(function ($item) use (&$result, $itemColumns) {
                if (count($itemColumns)) {
                    $items = $item->getItems(true);
                    $items->columns($itemColumns);
                    $items->load(true, true);
                }
                $result[] = $item->toArray() + ['items' => isset($item) ? $items->toArray() : []];
            });
            return $result;
        }
        return $this->getResponse()->withStatus(403);
    }
}
