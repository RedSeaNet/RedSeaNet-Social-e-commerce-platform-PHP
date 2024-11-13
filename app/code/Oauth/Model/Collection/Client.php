<?php

namespace Redseanet\Oauth\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Client extends AbstractCollection
{
    protected function construct()
    {
        $this->init('oauth_client');
    }
}
