<?php

namespace Redseanet\Message\ViewModel;

use Redseanet\Message\Model\Collection\Messages as Collection;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use DateTime;

class Notifications extends Template
{
    use \Redseanet\Lib\Traits\Filter;

    protected $current = null;

    public function getNotifications($condition = [])
    {
        $collection = new Collection();
        $segment = new Segment('customer');
        $customerId = $segment->get('customer')['id'];
        $condition['customer_id'] = $customerId;
        $collection->where($collection);
        $this->filter($collection, $condition);
        return $collection;
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
