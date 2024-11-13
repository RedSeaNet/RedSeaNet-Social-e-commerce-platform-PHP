<?php

namespace Redseanet\Email\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Subscriber extends AbstractCollection
{
    protected function construct()
    {
        $this->init('newsletter_subscriber');
    }
}
