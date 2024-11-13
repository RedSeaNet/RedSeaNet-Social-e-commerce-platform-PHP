<?php

namespace Redseanet\Forum\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class CustomerLike extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_customer_like');
    }
}
