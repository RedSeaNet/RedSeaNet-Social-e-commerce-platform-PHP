<?php

namespace Redseanet\Forum\Model;

use Redseanet\Lib\Model\AbstractModel;

class CustomerLike extends AbstractModel
{
    protected function construct()
    {
        $this->init('forum_customer_like', 'id', ['id', 'customer_id', 'like_customer_id']);
    }

    public function liked($id)
    {
        return (bool) count($this->select(['customer_id' => $this->getId(), 'like_customer_id' => $id])->toArray());
    }

    public function like($id)
    {
        $ref = new static();
        $ref->load($this->getId());
        if ($this->liked($id)) {
            $this->delete(['customer_id' => $this->getId(), 'like_customer_id' => $id]);
            $this->flushList($this->getCacheKey());
            return $this;
        } else {
            $this->insert(['customer_id' => $this->getId(), 'like_customer_id' => $id]);
            $this->flushList($this->getCacheKey());
            return $this;
        }
        return $this;
    }

    public function justLike($id)
    {
        $ref = new static();
        $ref->load($this->getId());
        if (!$this->liked($id)) {
            $this->insert(['customer_id' => $this->getId(), 'like_customer_id' => $id]);
            $this->flushList($this->getCacheKey());
            return $this;
        }
        return $this;
    }

    public function clearNewFans($customer_id)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $status = $this->update(['is_new' => 0], ['like_customer_id' => $customer_id, 'is_new' => 1]);
        $this->flushList($this->getCacheKey());
        return $status;
    }

    public function clearNewFollow($customer_id)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $status = $this->update(['is_new' => 0], ['customer_id' => $customer_id, 'is_new' => 1]);
        $this->flushList($this->getCacheKey());
        return $status;
    }
}
