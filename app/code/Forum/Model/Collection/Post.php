<?php

namespace Redseanet\Forum\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Select;

class Post extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_post');
    }

    protected function afterLoad(&$result)
    {
        foreach ($result as $key => $item) {
            $content = @gzdecode($item['content']);
            if ($content !== false) {
                $result[$key]['content'] = $content;
            }
        }
        parent::afterLoad($result);
    }

    public function getDynamic($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $post = new Select('forum_customer_like');
        $post->columns(['like_customer_id']);
        $this->select->join('forum_customer_like', 'forum_customer_like.like_customer_id=forum_post.customer_id', [], 'left')
                ->where(['forum_customer_like.customer_id' => $customer_id]);
        return $this;
    }

    public function getCollected($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $collect = new Select('forum_post_favorite');
        $collect->columns(['post_id']);
        $this->select->join('forum_post_favorite', 'forum_post_favorite.post_id =forum_post.id ', [], 'left')
                ->where(['forum_post_favorite.customer_id' => $customer_id]);
        return $this;
    }
}
