<?php

namespace Redseanet\Sales\Model\Api\Rest;

use Redseanet\Api\Model\Api\Rest\AbstractHandler;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Session\Segment;
use Redseanet\Oauth\Model\Token;
use Redseanet\Sales\Model\Cart as Model;
use Redseanet\Sales\Model\Collection\Cart as Collection;

class Cart extends AbstractHandler
{
    public function getCart()
    {
        $data = $this->getRequest()->getQuery();
        $columns = $this->getAttributes('cart');
        if (count($columns)) {
            if ($this->authOptions['validation'] > 0) {
                if (isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                    $token = new Token();
                    $token->load($data['openId'], 'open_id');
                    unset($data['openId']);
                    $data['customer_id'] = $token['customer_id'];
                    $data['status'] = 1;
                    $data['limit'] = 1;
                    $data['desc'] = 'id';
                } else {
                    return $this->getResponse()->withStatus(400);
                }
            }
            $collection = new Collection();
            $collection->columns($columns);
            $this->filter($collection, $data);
            $result = [];
            $itemColumns = $this->getAttributes('cart_items');
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

    public function putCartItem()
    {
        if ($this->authOptions['validation'] > 0) {
            $data = $this->getRequest()->getPost();
            if ((!empty($data['id']) || !empty($data['product_id']) && !empty($data['warehouse_id'])) && !empty($data['qty']) &&
                    isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                $token = new Token();
                $token->load($data['openId'], 'open_id');
                unset($data['openId']);
                $segment = new Segment('customer');
                $segment->set('hasLoggedIn', true)
                        ->set('customer', (new Customer())->setId($token['customer_id']));
                $cart = Model::instance();
                if (!empty($data['id'])) {
                    $cart->changeQty($data['id'], $data['qty']);
                } else {
                    $cart->addItem($data['product_id'], $data['qty'], $data['warehouse_id'], $data['options'] ?? [], $data['sku'] ?? '');
                }
                return $this->getResponse()->withStatus(202);
            } else {
                return $this->getResponse()->withStatus(400);
            }
        }
        return $this->getResponse()->withStatus(403);
    }

    public function deleteCartItem()
    {
        if ($this->authOptions['validation'] > 0) {
            $data = $this->getRequest()->getPost();
            if (!empty($data['id']) && isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                $token = new Token();
                $token->load($data['openId'], 'open_id');
                unset($data['openId']);
                $segment = new Segment('customer');
                $segment->set('hasLoggedIn', true)
                        ->set('customer', (new Customer())->setId($token['customer_id']));
                $cart = Model::instance();
                $cart->removeItem($data['id']);
                return $this->getResponse()->withStatus(202);
            } else {
                return $this->getResponse()->withStatus(400);
            }
        }
        return $this->getResponse()->withStatus(403);
    }
}
