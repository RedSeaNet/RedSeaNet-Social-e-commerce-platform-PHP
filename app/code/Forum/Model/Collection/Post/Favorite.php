<?php

namespace Redseanet\Forum\Model\Collection\Post;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Select;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;

class Favorite extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_post_favorite');
    }

    public function getCollected($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $this->select->where(['forum_post_favorite.customer_id' => $customer_id])
                ->join('forum_post', 'forum_post.id = forum_post_favorite.post_id', ['like' => 'like', 'images' => 'images'], 'left')
                ->join('customer_1_index', 'customer_1_index.id=forum_post.customer_id', ['username' => 'username', 'avatar' => 'avatar'], 'left');
        return $this;
    }

    public function getBeCollected($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $post = new Select('forum_post');
        $post->columns(['id']);
        $post->where(['customer_id' => $customer_id]);
        $this->select->join('forum_post', 'forum_post.id = forum_post_favorite.post_id', ['title', 'images'], 'left')
                ->join('customer_1_index', 'customer_1_index.id=forum_post_favorite.customer_id', ['author' => 'username', 'avatar' => 'avatar'], 'left')
        ->where->in('post_id', $post);
        return $this;
    }

    public function getBeCollectedCount($customer_id = null, $is_new = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }

        $post = new Select('forum_post');
        $post->columns(['id']);
        $post->where(['customer_id' => $customer_id]);

        $this->select->columns(['count' => new Expression('count(1)')]);
        $where = new Where();
        $where->in('post_id', $post);

        if (!is_null($is_new)) {
            $where->equalTo('is_new', $is_new);
        }
        $this->select->where($where);

        return $this;
    }

    public function getFavorited($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $this->select->join('forum_post', 'forum_post.id = forum_post_favorite.post_id', 'title', 'left')
                ->join('customer_1_index', 'customer_1_index.id=forum_post_favorite.customer_id', ['author' => 'username', 'avatar' => 'avatar'], 'left')
                ->where(['forum_post_favorite.customer_id' => $customer_id]);
        return $this;
    }
}
