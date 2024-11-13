<?php

namespace Redseanet\LiveChat\Model;

use Redseanet\Lib\Model\AbstractModel;

class Status extends AbstractModel
{
    protected function construct()
    {
        $this->init('livechat_status', 'id', ['id', 'status']);
    }

    protected function afterSave()
    {
        $this->flushList('livechat_record');
        parent::afterSave();
    }
}
