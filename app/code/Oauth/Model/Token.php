<?php

namespace Redseanet\Oauth\Model;

use Redseanet\Lib\Model\AbstractModel;

class Token extends AbstractModel
{
    protected function construct()
    {
        $this->init('oauth_token', 'id', ['id', 'consumer_id', 'open_id', 'admin_id', 'customer_id', 'status']);
    }
}
