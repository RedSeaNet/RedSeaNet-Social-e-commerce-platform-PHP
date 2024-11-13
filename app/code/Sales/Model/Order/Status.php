<?php

namespace Redseanet\Sales\Model\Order;

use Exception;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Sales\Model\Collection\Order\Status as Collection;

class Status extends AbstractModel
{
    protected function construct()
    {
        $this->init('sales_order_status', 'id', ['id', 'phase_id', 'name', 'is_default']);
    }

    public function getPhase()
    {
        if (isset($this->storage['phase_id'])) {
            return (new Phase())->load($this->storage['phase_id']);
        }
        return null;
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    public function afterSave()
    {
        parent::afterSave();
        if ($this->offsetGet('is_default')) {
            $collection = new Collection();
            $collection->where(['is_default' => 1, 'phase_id' => $this->storage['phase_id']])
            ->where->notEqualTo('id', $this->getId());
            foreach ($collection as $status) {
                $status->setData('is_default', 0)->save();
            }
        } else {
            $collection = new Collection();
            $collection->where(['phase_id' => $this->storage['phase_id']])
            ->where->notEqualTo('id', $this->getId());
            if ($collection->count()) {
                $flag = true;
                foreach ($collection as $status) {
                    if ($status->offsetGet('is_default')) {
                        $flag = false;
                    }
                }
                if ($flag) {
                    $collection[0]->setData('is_default', 1)->save();
                }
            } else {
                throw new Exception('There must be another status in the same phase.');
            }
        }
        $this->commit();
    }
}
