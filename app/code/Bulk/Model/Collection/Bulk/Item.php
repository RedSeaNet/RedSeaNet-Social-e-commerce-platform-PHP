<?php

namespace Redseanet\Bulk\Model\Collection\Bulk;

use Redseanet\Lib\Model\AbstractCollection;

class Item extends AbstractCollection
{
    protected function construct()
    {
        $this->init('bulk_sale_item');
    }
}
