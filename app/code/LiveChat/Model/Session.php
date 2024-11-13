<?php

namespace Redseanet\LiveChat\Model;

use Redseanet\Lib\Model\AbstractModel;

class Session extends AbstractModel
{
    protected function construct()
    {
        $this->init('livechat_session', 'session_id', ['session_id', 'customer_1', 'customer_2']);
    }
}
