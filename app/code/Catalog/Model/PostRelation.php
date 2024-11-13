<?php

namespace Redseanet\Catalog\Model;

use Redseanet\Lib\Model\AbstractModel;

class PostRelation extends AbstractModel
{
    protected function construct()
    {
        $this->init('forum_product_relation', 'id', ['post_id', 'category_id', 'product_id', 'sort_order']);
    }
}
