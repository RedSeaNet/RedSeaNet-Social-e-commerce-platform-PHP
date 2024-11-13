<?php

namespace Redseanet\Forum\Model\Post;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Customer\Model\Customer;
use Redseanet\Forum\Model\Post;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class Favorite extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected $customer = null;

    protected function construct()
    {
        $this->init('forum_post_favorite', 'id', ['id', 'post_id', 'customer_id', 'is_new']);
    }

    public function getCustomer()
    {
        if (is_null($this->customer)) {
            $this->customer = new Customer($this->storage['language_id']);
            $this->customer->load($this->storage['customer_id']);
        }
        return $this->customer;
    }

    public function clearNewBeCollected($customer_id)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $status = $this->update(['is_new' => 0], ['is_new' => 1]);
        $this->flushList($this->getCacheKey());
        return $status;
    }
}
