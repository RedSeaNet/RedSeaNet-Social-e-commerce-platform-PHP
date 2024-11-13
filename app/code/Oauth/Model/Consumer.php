<?php

namespace Redseanet\Oauth\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Api\Model\Rest\Role;

class Consumer extends AbstractModel
{
    protected function construct()
    {
        $this->init('oauth_consumer', 'id', ['id', 'name', 'role_id', 'key', 'secret', 'public_key', 'private_key', 'phrase', 'callback_url', 'rejected_callback_url']);
    }

    public function getRole()
    {
        if (!empty($this->storage['role_id'])) {
            $role = new Role();
            $role->load($this->storage['role_id']);
            return $role;
        }
        return null;
    }
}
