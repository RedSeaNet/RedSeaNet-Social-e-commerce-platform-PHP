<?php

namespace Redseanet\Api\Model\Soap;

use Redseanet\Lib\Model\AbstractModel;
use Laminas\Math\Rand;

class Session extends AbstractModel
{
    protected function construct()
    {
        $this->init('api_soap_session', 'session_id', ['session_id', 'user_id', 'log_date']);
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        return false;
    }

    protected function beforeSave()
    {
        if (!$this->getId()) {
            while (1) {
                $id = Rand::getString(Rand::getInteger(30, 40));
                $tmp = new static();
                $tmp->load($id);
                if (!$tmp->getId()) {
                    break;
                }
            }
            $this->setId($id);
        }
        parent::beforeSave();
    }
}
