<?php

namespace Redseanet\Log\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Payment extends AbstractCollection
{
    protected function construct()
    {
        $this->init('log_payment');
    }
}
