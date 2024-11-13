<?php

namespace Redseanet\Api\Model\Collection\Rest;

use Redseanet\Lib\Model\AbstractCollection;

class Attribute extends AbstractCollection
{
    protected function construct()
    {
        $this->init('api_rest_attribute');
    }
}
