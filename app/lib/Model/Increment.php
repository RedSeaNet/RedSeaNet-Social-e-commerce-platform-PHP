<?php

namespace Redseanet\Lib\Model;

class Increment extends AbstractModel
{
    protected function construct()
    {
        $this->init('core_increment', 'type', ['type', 'store_id', 'prefix', 'last_id']);
    }

    public function getIncrementId($length = '')
    {
        if (!$this->isLoaded) {
            return '';
        }
        $this->setData('last_id', function_exists('bcadd') ? bcadd($this->storage['last_id'], 1) : $this->storage['last_id'] + 1)->save();
        return $this->storage['prefix'] . sprintf('%0' . $length . 's', $this->storage['last_id']);
    }
}
