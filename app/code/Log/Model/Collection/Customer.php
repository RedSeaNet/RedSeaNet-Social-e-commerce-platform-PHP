<?php

namespace Redseanet\Log\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Customer extends AbstractCollection
{
    protected function construct()
    {
        $this->init('log_customer');
    }
}
