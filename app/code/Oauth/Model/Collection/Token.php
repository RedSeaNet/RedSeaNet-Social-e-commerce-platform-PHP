<?php

namespace Redseanet\Oauth\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Token extends AbstractCollection
{
    protected function construct()
    {
        $this->init('oauth_token');
    }
}
