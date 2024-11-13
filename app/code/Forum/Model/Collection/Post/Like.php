<?php

namespace Redseanet\Forum\Model\Collection\Post;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Select;

class Like extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_like');
    }

    public function getMyLikes($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $this->select->where(['forum_like.customer_id' => $customer_id])
                ->join('forum_post', 'forum_post.id = forum_like.post_id', ['title', 'images'], 'left')
                ->join('forum_post_review', 'forum_post_review.id = forum_like.review_id', ['review_content' => 'content'], 'left')
                ->join('customer_1_index', 'customer_1_index.id=forum_like.author_id', ['author' => 'username', 'avatar' => 'avatar'], 'left');

        return $this;
    }

    public function getBeLikes($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $post = new Select('forum_post');
        $post->columns(['id']);
        $post->where(['customer_id' => $customer_id]);
        $this->select->join('forum_post', 'forum_post.id = post_id', ['title', 'images'], 'left')
                ->join('forum_post_review', 'forum_post_review.id = forum_like.review_id', ['review_content' => 'content'], 'left')
                ->join('customer_1_index', 'customer_1_index.id=forum_like.customer_id', ['author' => 'username', 'avatar' => 'avatar'], 'left')
        ->where->in('forum_like.post_id', $post, 'forum_like.review_id', $post);
        return $this;
    }
}
