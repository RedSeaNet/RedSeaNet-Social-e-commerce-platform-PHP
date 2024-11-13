<?php

namespace Redseanet\Catalog\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class PostRelation extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_product_relation');
    }
}
