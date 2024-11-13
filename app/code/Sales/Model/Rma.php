<?php

namespace Redseanet\Sales\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Sales\Model\Rma\Item;
use Redseanet\Sales\Model\Collection\Order\Item as ItemCollection;

class Rma extends AbstractModel
{
    protected $order = null;
    protected $items = null;

    protected function construct()
    {
        $this->init('sales_rma', 'id', ['id', 'order_id', 'customer_id', 'currency', 'amount', 'reason', 'service', 'status', 'created_at', 'updated_at']);
    }

    protected function afterSave()
    {
        if (!empty($this->storage['qty'])) {
            foreach ($this->storage['qty'] as $id => $qty) {
                if ($qty) {
                    $item = new Item();
                    $item->setData([
                        'rma_id' => $this->getId(),
                        'item_id' => $id,
                        'qty' => $qty
                    ])->save();
                }
            }
        }
        parent::afterSave();
    }

    public function addComment($data)
    {
        if ($this->getId()) {
            if (is_scalar($data)) {
                $data = ['comment' => gzencode($data)];
            } else {
                $data['comment'] = gzencode($data['comment']);
            }
            $tableGateway = $this->getTableGateway('log_rma');
            $tableGateway->insert(['rma_id' => $this->getId()] + $data);
            $this->flushList('log_rma');
        }
        return $this;
    }

    public function getComments()
    {
        if ($this->getId()) {
            $result = $this->fetchList('rma_id=' . $this->getId(), 'log_rma');
            if (!$result = $this->fetchList('rma_id=' . $this->getId(), 'log_rma')) {
                $tableGateway = $this->getTableGateway('log_rma');
                $select = $tableGateway->getSql()->select();
                $select->where(['rma_id' => $this->getId()])
                        ->order('created_at DESC');
                $result = $tableGateway->selectWith($select)->toArray();
                foreach ($result as &$item) {
                    $item['comment'] = @gzdecode($item['comment']);
                }
                $this->addCacheList('rma_id=' . $this->getId(), $result, 'log_rma');
            }
            return $result;
        }
        return [];
    }

    public function getOrder()
    {
        if (is_null($this->order)) {
            if (!empty($this->storage['order_id'])) {
                $order = new Order();
                $order->load($this->storage['order_id']);
                if ($order->getId()) {
                    $this->order = $order;
                }
            }
        }
        return $this->order;
    }

    public function getItems($force = false)
    {
        if ($force || is_null($this->items)) {
            $items = new ItemCollection();
            $items->join('sales_rma_item', 'sales_order_item.id=sales_rma_item.item_id', ['refunded_qty' => 'qty'], 'right')
                    ->where(['rma_id' => $this->getId()]);
            $result = [];
            $items->walk(function ($item) use (&$result) {
                $result[$item['id']] = $item;
            });
            $this->items = $result;
            if ($force) {
                return $items;
            }
        }
        return $this->items;
    }
}
