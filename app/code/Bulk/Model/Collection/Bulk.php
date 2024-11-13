<?php

namespace Redseanet\Bulk\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Bulk extends AbstractCollection
{
    protected function construct()
    {
        $this->init('bulk_sale');
    }
}
