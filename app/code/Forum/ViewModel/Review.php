<?php

namespace Redseanet\Forum\ViewModel;

use DateTime;
use Redseanet\Forum\Model\Post;
use Redseanet\Lib\ViewModel\Template;

class Review extends Template
{
    protected $current = null;

    public function getReviews()
    {
        $post = new Post();
        $post->setId($this->getVariable('post_id'));
        $reviews = $post->getReviews();
        $reviews->getSelect()->where->greaterThan('status', 0);
        return $reviews;
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
        if ($days) {
            return $days . ' Days Ago';
        } else {
            return $dt->format('H:i');
        }
    }
}
