<?php

namespace Redseanet\Customer\Source;

use Redseanet\Customer\Model\Collection\Group as Collection;
use Redseanet\Lib\Source\SourceInterface;

class Group implements SourceInterface
{
    public function getSourceArray($flag = false)
    {
        $collection = new Collection();
        $collection->columns(['id', 'name']);
        $record = $flag ? [0 => 'Not Logged In'] : [];
        foreach ($collection as $item) {
            $record[$item['id']] = $item['name'];
        }
        return $record;
    }
}
