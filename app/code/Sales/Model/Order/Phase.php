<?php

namespace Redseanet\Sales\Model\Order;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Sales\Model\Collection\Order\Status as Collection;

class Phase extends AbstractModel
{
    protected function construct()
    {
        $this->init('sales_order_phase', 'id', ['id', 'code', 'name']);
    }

    public function save($constraint = [], $insertForce = false)
    {
        trigger_error('Call to undefined method Redseanet\\Sales\\Model\\Order\\Phase::save()', E_USER_ERROR);
    }

    public function getStatus()
    {
        if ($this->getId()) {
            $status = new Collection();
            $status->where(['phase_id' => $this->getId()])->order('id ASC, is_default DESC');
            return $status;
        }
        return [];
    }

    public function getDefaultStatus()
    {
        if ($this->getId()) {
            $status = new Collection();
            $status->where(['phase_id' => $this->getId(), 'is_default' => 1])->limit(1);
            if (count($status)) {
                return $status[0];
            }
        }
        return null;
    }
}
