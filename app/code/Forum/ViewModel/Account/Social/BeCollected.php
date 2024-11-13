<?php

namespace Redseanet\Forum\ViewModel\Account\Social;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Forum\Model\Collection\Post\Favorite;
use Redseanet\Forum\Model\Post\Favorite as Model;
use DateTime;

class BeCollected extends Template
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

    public function getBeCollected($customer_id = null)
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
        $be_collects = $favorited->getBeCollected($customer_id);
        unset($query['is_json']);
        $this->filter($be_collects, $query);
        return $be_collects;
    }

    public function clearNewBeCollectCount($customer_id = null)
    {
        $customer_id = $customer_id ?? (new Segment('customer'))->get('customer')['id'];
        $like = new Model();
        $clear = $like->clearNewBeCollected($customer_id);
        return $clear;
    }
}
