<?php

namespace Redseanet\Forum\Model\Collection\Post;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;

class RecommendLink extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_post_recommend_link');
    }

    protected function afterLoad(&$result)
    {
        parent::afterLoad($result);
    }
}
