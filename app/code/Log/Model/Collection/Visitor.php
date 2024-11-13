<?php

namespace Redseanet\Log\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Visitor extends AbstractCollection
{
    protected function construct()
    {
        $this->init('log_visitor');
    }
}
