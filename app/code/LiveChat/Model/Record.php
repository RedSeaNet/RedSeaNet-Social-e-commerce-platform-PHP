<?php

namespace Redseanet\LiveChat\Model;

use Redseanet\Lib\Model\AbstractModel;

class Record extends AbstractModel
{
    protected function construct()
    {
        $this->init('livechat_record', 'session_id', ['session_id', 'sender', 'type', 'message', 'partial']);
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        return false;
    }
}
