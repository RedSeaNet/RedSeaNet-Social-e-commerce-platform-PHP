<?php

namespace Redseanet\Notifications\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Increment;

class Notifications extends AbstractModel
{
    protected function construct()
    {
        $this->init('core_notifications', 'id', ['id', 'title', 'content', 'params', 'area', 'level', 'language_id', 'is_app', 'is_sms', 'status', 'customer_id', 'sender_id', 'administrator_id', 'type']);
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        $result = parent::isUpdate($constraint, $insertForce);
        if (!$result && !$this->getId()) {
            $increment = new Increment();
            $increment->load($this->tableName);
            $this->setId($increment->getIncrementId());
        }
        return $result;
    }
}
