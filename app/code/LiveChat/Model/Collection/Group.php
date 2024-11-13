<?php

namespace Redseanet\LiveChat\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Group extends AbstractCollection
{
    protected function construct()
    {
        $this->init('livechat_group');
    }
}
