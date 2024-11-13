<?php

namespace Redseanet\Forum\Model\Collection\Poll;

use Redseanet\Lib\Model\AbstractCollection;

class Option extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_poll_option');
    }
}
