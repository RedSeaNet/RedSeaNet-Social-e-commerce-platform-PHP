<?php

namespace Redseanet\Notifications\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Notifications extends AbstractCollection
{
    protected function construct()
    {
        $this->init('core_notifications');
    }
}
