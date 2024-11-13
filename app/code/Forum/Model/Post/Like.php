<?php

namespace Redseanet\Forum\Model\Post;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class Like extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected function construct()
    {
        $this->init('forum_like', 'id', ['id', 'post_id', 'review_id', 'customer_id', 'author_id']);
    }

    public function getCustomer()
    {
        $customer = new Customer();
        $customer->load($this->storage['customer_id']);
        return $customer;
    }

    public function clearNewBelike($customer_id)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $status = $this->update(['is_new_be_like' => 0], ['author_id' => $customer_id, 'is_new_be_like' => 1]);
        $this->flushList($this->getCacheKey());
        return $status;
    }
}
