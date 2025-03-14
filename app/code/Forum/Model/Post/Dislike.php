<?php

namespace Redseanet\Forum\Model\Post;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class Dislike extends AbstractModel {

    use \Redseanet\Lib\Traits\Url;

    protected function construct() {
        $this->init('forum_dislike', 'id', ['id', 'post_id', 'review_id', 'customer_id', 'author_id']);
    }

    public function getCustomer() {
        $customer = new Customer();
        $customer->load($this->storage['customer_id']);
        return $customer;
    }

}
