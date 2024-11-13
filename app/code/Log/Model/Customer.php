<?php

namespace Redseanet\Log\Model;

use Redseanet\Customer\Model\Customer as Model;
use Redseanet\Lib\Model\AbstractModel;

class Customer extends AbstractModel
{
    protected function construct()
    {
        $this->init('log_customer', 'id', ['id', 'customer_id', 'store_id', 'session_id', 'remote_addr', 'http_referer', 'http_user_agent', 'http_accept_charset', 'http_accept_language']);
    }

    public function getCustomer()
    {
        if (!empty($this->storage['customer_id'])) {
            $customer = new Model();
            $customer->load($this->storage['customer_id']);
            return $customer;
        }
        return null;
    }
}
