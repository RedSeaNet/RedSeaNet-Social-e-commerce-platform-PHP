<?php

namespace Redseanet\Notifications\ViewModel;

use Redseanet\Notifications\Model\Collection\Notifications as Collection;
use Redseanet\Notifications\Model\Notifications as Model;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use DateTime;
use Laminas\Db\Sql\Expression;

class Notifications extends Template
{
    use \Redseanet\Lib\Traits\Filter;
    use \Redseanet\Lib\Traits\DB;

    protected $current = null;

    public function getNotifications($condition = [])
    {
        $collection = new Collection();
        $segment = new Segment('customer');
        $customerId = $segment->get('customer')['id'];
        $condition['core_notifications.customer_id'] = $customerId;
        $condition['desc'] = 'core_notifications.created_at';
        $collection->join('customer_1_index', 'customer_1_index.id=core_notifications.sender_id', ['username', 'avatar'], 'left');
        $this->filter($collection, $condition);
        return $collection->load(true, true);
    }

    public function getNotificationsCount($condition = [])
    {
        $collection = new Collection();
        $segment = new Segment('customer');
        $customerId = $segment->get('customer')['id'];
        $condition['customer_id'] = $customerId;
        $collection->columns(['count' => new Expression('count(1)')]);
        $collection->where($condition);
        $collection->load(true, true);
        return isset($collection[0]['count']) ? $collection[0]['count'] : 0;
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

    public function updateReadStatus($id)
    {
        $model = new Model();
        $model->load($id);
        $model->setData('status', 1);
        $model->save();
    }
}
