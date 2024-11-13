<?php

namespace Redseanet\Sales\Model\Api\Rest;

use Redseanet\Api\Model\Api\Rest\AbstractHandler;
use Redseanet\Oauth\Model\Token;
use Redseanet\Sales\Model\Collection\Invoice as InvoiceCollection;
use Redseanet\Sales\Model\Collection\Order as OrderCollection;

class Invoice extends AbstractHandler
{
    public function getInvoice()
    {
        $data = $this->getRequest()->getQuery();
        $columns = $this->getAttributes('invoice');
        if (count($columns)) {
            if ($this->authOptions['validation'] > 0) {
                if (isset($data['openId']) && $data['openId'] === $this->authOptions['open_id']) {
                    $token = new Token();
                    $token->load($data['openId'], 'open_id');
                    unset($data['openId']);
                    $order = new OrderCollection();
                    $order->columns(['id'])
                            ->where(['customer_id' => $token['customer_id']]);
                } else {
                    return $this->getResponse()->withStatus(400);
                }
            }
            $collection = new InvoiceCollection();
            $collection->columns($columns);
            $this->filter($collection, $data);
            if ($order) {
                $collection->in('order_id', $order);
            }
            $result = [];
            $itemColumns = $this->getAttributes('invoice_items');
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
