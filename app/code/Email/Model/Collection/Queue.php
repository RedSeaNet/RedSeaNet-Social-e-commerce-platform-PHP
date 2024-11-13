<?php

namespace Redseanet\Email\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Queue extends AbstractCollection
{
    protected function construct()
    {
        $this->init('email_queue');
    }
}
