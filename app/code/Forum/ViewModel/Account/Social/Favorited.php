<?php

namespace Redseanet\Forum\ViewModel\Account\Social;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Forum\Model\Collection\Post\Favorite;
use Redseanet\Forum\Model\Post\Favorite as Model;
use DateTime;
use Redseanet\Forum\Model\Collection\Post as PostCollection;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Redseanet\Lib\Bootstrap;

class Favorited extends Template
{
    use \Redseanet\Lib\Traits\Filter;

    protected $current = null;

    protected function getCurrent()
    {
        if (is_null($this->current)) {
            $this->current = new DateTime();
        }
        return $this->current;
    }

    public function getTime($time)
    {
        $dt = new DateTime($time);
        $days = $dt->diff($this->getCurrent())->format('%a');
        if ($days && $days > 1) {
            return $this->translate('%d Days Ago', [$days]);
        } elseif ((int) $days == 1) {
            return $this->translate('Yesterday %d', [$dt->format('H')]);
        } else {
            return $this->translate('Today') . $dt->format('H:i');
        }
    }

    public function getFavorited($customer_id = null)
    {
        if (empty($customer_id)) {
            $customer = (new Segment('customer'))->get('customer');
            $customer_id = $customer['id'];
        }
        if (is_null($customer_id)) {
            return null;
        }
        $query = $this->getQuery();
        $favorited = new Favorite();
        $favorited_list = $favorited->getFavorited($customer_id);
        unset($query['is_json']);
        $this->filter($favorited_list, $query);
        return $favorited_list;
    }

    public function getFavoritedWithPosts($customer_id = null)
    {
        if (empty($customer_id)) {
            $customer = (new Segment('customer'))->get('customer');
            $customer_id = $customer['id'];
        }
        if (is_null($customer_id)) {
            return null;
        }
        $query = $this->getQuery();
        $views = new Select('log_visitor');
        $views->columns(['count' => new Expression('count(1)')])
                ->group('post_id')
        ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');

        $favorited = new PostCollection();
        $likedSelect = new Select();
        $likedSelect->from('forum_like');
        $likedSelect->columns(['liked' => new Expression('count(forum_like.post_id)')]);
        $likedSelect->where('`forum_like`.`customer_id`=' . $customer_id . ' and `forum_like`.`post_id`=`forum_post`.`id`');
        $favorited = new PostCollection();
        $favoritedSelect = $favorited->getSelect();
        $favoritedSelect->join('forum_post_favorite', 'forum_post.id = forum_post_favorite.post_id', ['favorite_id' => 'id', 'favorite_created_at' => 'created_at'], 'left')
                ->join('customer_1_index', 'customer_1_index.id=forum_post_favorite.customer_id', ['author' => 'username', 'avatar' => 'avatar'], 'left')
                ->where(['forum_post_favorite.customer_id' => $customer_id]);
        $favorited->columns(['*', 'views' => $views, 'liked' => $likedSelect])
                ->where(['forum_post.is_draft' => 0])
                ->order('forum_post.is_top DESC')
        ->where->greaterThan('forum_post.status', 0);
        //echo $favorited->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        unset($query['is_json']);
        $this->filter($favorited, $query);
        return $favorited;
    }
}
