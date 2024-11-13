<?php

namespace Redseanet\Customer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Media extends AbstractCollection
{
    protected function construct()
    {
        $this->init('social_media');
    }
}
