<?php

namespace Redseanet\Forum\ViewModel\Account\Social;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Forum\Model\Collection\Post\Review;
use DateTime;

class Reply extends Template
{
    use \Redseanet\Lib\Traits\Filter;

    protected $current = null;

    public function getMyReviews($customer_id = null)
    {
        if (empty($customer_id)) {
            $customer = (new Segment('customer'))->get('customer');
            $customer_id = $customer['id'];
        }
        if (is_null($customer_id)) {
            return null;
        }
        $query = $this->getQuery();
        $review = new Review();
        $my_reviews = $review->getMyReviews($customer_id);
        unset($query['is_json']);
        $this->filter($my_reviews, $query);
        return $my_reviews;
    }

    public function getMyReferences($customer_id = null)
    {
        if (empty($customer_id)) {
            $customer = (new Segment('customer'))->get('customer');
            $customer_id = $customer['id'];
        }
        if (is_null($customer_id)) {
            return null;
        }
        $query = $this->getQuery();
        $review = new Review();
        $my_reviews = $review->getMyReferences($customer_id);
        unset($query['is_json']);
        $this->filter($my_reviews, $query);
        return $my_reviews;
    }

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
}
