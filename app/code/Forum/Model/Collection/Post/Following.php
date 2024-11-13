<?php

namespace Redseanet\Forum\Model\Collection\Post;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Select;
use Redseanet\Customer\Model\Collection\Customer;

class Following extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_customer_like');
    }

    public function getFollow($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $this->select->where(['forum_customer_like.customer_id' => $customer_id])
                ->join('customer_1_index', 'customer_1_index.id=forum_customer_like.like_customer_id', ['username' => 'username', 'avatar' => 'avatar', 'motto' => 'motto'], 'left');
        return $this;
    }

    public function getFans($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $post = new Select('customer_1_index');
        $post->columns(['id']);
        $post->where(['id' => $customer_id]);
        $this->select->join('customer_1_index', 'customer_1_index.id=forum_customer_like.customer_id', ['username' => 'username', 'avatar' => 'avatar', 'motto' => 'motto'], 'left')
        ->where->in('forum_customer_like.like_customer_id', $post);
        return $this;
    }

    public function getDynamic($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $post = new Select('forum_post');
        $post->columns(['customer_id']);
        $customer = new Customer();
        $customer->columns(['id']);
        $this->select->join('forum_post', 'forum_post.customer_id=forum_customer_like.like_customer_id', ['post_id' => 'id', 'images' => 'images', 'title' => 'title', 'content' => 'content', 'like' => 'like', 'reviews' => 'reviews', 'collections' => 'collections', 'created' => 'created_at'], 'left')
                ->join('customer_1_index', 'customer_1_index.id=forum_customer_like.like_customer_id', ['username' => 'username', 'avatar' => 'avatar', 'motto' => 'motto'], 'left')
        ->where->in('forum_customer_like.like_customer_id', $post);
        return $this;
    }
}
