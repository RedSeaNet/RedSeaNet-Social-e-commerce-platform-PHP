<?php

namespace Redseanet\LiveChat\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Session extends AbstractCollection
{
    protected function construct()
    {
        $this->init('livechat_session');
    }
}
