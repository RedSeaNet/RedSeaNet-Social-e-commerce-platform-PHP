<?php

namespace Redseanet\Promotion\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Condition extends AbstractCollection
{
    protected function construct()
    {
        $this->init('promotion_condition');
    }
}
