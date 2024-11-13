<?php

namespace Redseanet\Sales\Source\Order;

use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Sales\Model\Collection\Order\Status as Collection;

class Status implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Collection();
        $result = [];
        $collection->order('phase_id, id');
        foreach ($collection as $item) {
            $result[$item['id']] = $item['name'];
        }
        return $result;
    }
}
