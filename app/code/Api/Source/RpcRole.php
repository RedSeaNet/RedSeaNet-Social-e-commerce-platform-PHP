<?php

namespace Redseanet\Api\Source;

use Redseanet\Api\Model\Collection\Rpc\Role as Collection;
use Redseanet\Lib\Source\SourceInterface;

class RpcRole implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Collection();
        $collection->columns(['id', 'name']);
        $record = [];
        foreach ($collection as $item) {
            if (!isset($record[$item['id']])) {
                $record[$item['id']] = [];
            }
            $record[$item['id']] = $item['name'];
        }
        return $record;
    }
}
