<?php

namespace Redseanet\Lib\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Cron extends AbstractCollection
{
    protected function construct()
    {
        $this->init('core_schedule');
    }
}
