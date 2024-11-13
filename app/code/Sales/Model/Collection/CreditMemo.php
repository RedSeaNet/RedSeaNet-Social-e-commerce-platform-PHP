<?php

namespace Redseanet\Sales\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class CreditMemo extends AbstractCollection
{
    protected function construct()
    {
        $this->init('sales_order_creditmemo');
    }
}
