<?php

namespace Redseanet\Forum\ViewModel;

use Redseanet\Forum\Model\Collection\CustomerLike;
use Redseanet\Forum\Model\Collection\Post\Favorite;
use Redseanet\Forum\Model\Collection\Post\Like;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Select;

class Account extends Template
{
    protected $customer_id = null;
    protected $like_users = null;
    protected $new_like_users = null;
    protected $fans = null;
    protected $new_fans = null;
    protected $be_likes = null;
    protected $new_be_likes = null;
    protected $be_collects = null;
    protected $new_be_collects = null;

    public function getCustomerId()
    {
        if (is_null($this->customer_id)) {
            $this->customer_id = (new Segment('customer'))->get('customer')['id'];
        }
        return $this->customer_id;
    }

    public function getLikeUserCount($customer_id = null)
    {
        $customer_id = $customer_id ?? $this->getCustomerId();
        $like_customer = new CustomerLike();
        $like_customer->columns(['count' => new Expression('count(1)')])
                ->where(['customer_id' => $customer_id]);
        return $like_customer->toArray()[0]['count'];
    }

    public function getFansCount($customer_id = null)
    {
        $customer_id = $customer_id ?? $this->getCustomerId();
        $fans = new CustomerLike();
        $fans->columns(['count' => new Expression('count(1)')])
                ->where(['like_customer_id' => $customer_id]);
        return $fans->toArray()[0]['count'];
    }

    public function getNewFollowCount($customer_id = null)
    {
        $customer_id = $customer_id ?? $this->getCustomerId();
        $fans = new CustomerLike();
        $fans->columns(['count' => new Expression('count(1)')])
                ->where(['customer_id' => $customer_id, 'is_new' => 1]);
        return $fans->toArray()[0]['count'];
    }

    public function getNewFansCount($customer_id = null)
    {
        $customer_id = $customer_id ?? $this->getCustomerId();
        $fans = new CustomerLike();
        $fans->columns(['count' => new Expression('count(1)')])
                ->where(['like_customer_id' => $customer_id, 'is_new' => 1]);
        return $fans->toArray()[0]['count'];
    }

    public function getBeLikeCount($customer_id = null)
    {
        $customer_id = $customer_id ?? $this->getCustomerId();
        $like = new Like();
        $like->columns(['count' => new Expression('count(1)')])
                ->where(['author_id' => $customer_id]);
        return $like->toArray()[0]['count'];
    }

    public function getNewBeLikeCount($customer_id = null)
    {
        $customer_id = $customer_id ?? $this->getCustomerId();
        $like = new Like();
        $like->columns(['count' => new Expression('count(1)')])
                ->where(['author_id' => $customer_id, 'is_new_be_like' => 1]);
        return $like->toArray()[0]['count'];
    }

    public function getBeFavoriteCount($customer_id = null)
    {
        $customer_id = $customer_id ?? $this->getCustomerId();
        $favorite = new Favorite();
        $favorite->getBeCollectedCount($customer_id);
        return $favorite->toArray()[0]['count'];
    }

    public function getNewBeFavoriteCount($customer_id = null)
    {
        $customer_id = $customer_id ?? $this->getCustomerId();
        $favorite = new Favorite();
        $favorite->getBeCollectedCount($customer_id, 1);
        return $favorite->toArray()[0]['count'];
    }

    public function getCount($customer_id = null)
    {
        if ($this->getSegment('customer')->get('hasLoggedIn')) {
            $customer_id = $customer_id ?? $this->getCustomerId();
            $count = $this->getNewFollowCount($customer_id) + $this->getNewFansCount($customer_id) + $this->getNewBeLikeCount($customer_id) + $this->getNewBeFavoriteCount($customer_id);
            return (int) $count;
        }
        return '';
    }
}
