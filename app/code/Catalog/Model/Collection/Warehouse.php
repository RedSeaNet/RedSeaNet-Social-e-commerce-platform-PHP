<?php

namespace Redseanet\Catalog\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Warehouse extends AbstractCollection
{
    protected function construct()
    {
        $this->init('warehouse');
    }
}
