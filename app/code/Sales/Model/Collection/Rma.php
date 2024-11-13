<?php

namespace Redseanet\Sales\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Rma extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_rma');
    }
}
