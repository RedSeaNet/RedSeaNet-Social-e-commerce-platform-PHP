<?php

namespace Redseanet\Catalog\Source;

use Redseanet\Catalog\Model\Collection\Warehouse as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\SourceInterface;

class Warehouse implements SourceInterface
{
    public function getSourceArray($isBackend = true)
    {
        $collection = new Collection();
        $result = [];
        foreach ($collection as $warehouse) {
            $result[$warehouse['id']] = $warehouse['name'];
        }
        return $result;
    }
}
