<?php

namespace Redseanet\Oauth\Model;

use Redseanet\Lib\Model\AbstractModel;

class Client extends AbstractModel
{
    protected function construct()
    {
        $this->init('oauth_client', 'customer_id', ['customer_id', 'oauth_server', 'open_id']);
    }

    public function save($constraint = [], $insertForce = true)
    {
        return parent::save($constraint, $insertForce);
    }

    public function doDelete($where)
    {
        $this->delete($where);
    }
}
