<?php

namespace Redseanet\Forum\ViewModel\Account\Social;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Forum\Model\Collection\Post\Like;
use Redseanet\Forum\Model\Post\Like as Model;
use DateTime;

class Liked extends \Redseanet\Customer\ViewModel\Account
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

    public function getMyLiked($customer_id = null)
    {
        if (empty($customer_id)) {
            $customer = (new Segment('customer'))->get('customer');
            $customer_id = $customer['id'];
        }
        if (is_null($customer_id)) {
            return null;
        }
        $query = $this->getQuery();
        $liked = new Like();
        $my_likes = $liked->getMyLikes($customer_id);
        unset($query['is_json']);
        $this->filter($my_likes, $query);
        return $my_likes;
    }

    public function getBeLikes($customer_id = null)
    {
        if (empty($customer_id)) {
            $customer = (new Segment('customer'))->get('customer');
            $customer_id = $customer['id'];
        }
        if (is_null($customer_id)) {
            return null;
        }
        $query = $this->getQuery();
        $belike = new Like();
        $belikes = $belike->getBeLikes($customer_id);
        $this->filter($belikes, $query);
        return $belikes;
    }

    public function clearNewBeLikeCount($customer_id = null)
    {
        $customer_id = $customer_id ?? (new Segment('customer'))->get('customer')['id'];
        $like = new Model();
        $clear = $like->clearNewBeLike($customer_id);
        return $clear;
    }
}
