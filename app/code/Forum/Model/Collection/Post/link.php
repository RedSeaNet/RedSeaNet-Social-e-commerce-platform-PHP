<?php

namespace Redseanet\Forum\Model\Collection\Post;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Select;

class Link extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_post_recommend_link');
    }
}
