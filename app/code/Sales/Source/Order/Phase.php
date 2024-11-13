<?php

namespace Redseanet\Sales\Source\Order;

use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Sales\Model\Collection\Order\Phase as Collection;

class Phase implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Collection();
        $result = [];
        foreach ($collection as $item) {
            $result[$item['id']] = $item['name'];
        }
        return $result;
    }
}
