<?php

namespace Redseanet\Email\Model;

use Redseanet\Lib\Model\AbstractModel;
use Laminas\Math\Rand;

class Subscriber extends AbstractModel
{
    protected function construct()
    {
        $this->init('newsletter_subscriber', 'id', ['id', 'email', 'name', 'language_id', 'status']);
    }

    public function beforeSave()
    {
        $this['code'] = Rand::getString(32);
        parent::beforeSave();
    }

    public function unsubscribe()
    {
        if ($this->isLoaded || $this->getId()) {
            $this->setData('status', 0)->save();
        }
        return $this;
    }
}
