<?php

namespace Redseanet\Lib\Source;

use Redseanet\Lib\Model\Collection\Merchant as Collection;

class Merchant implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Collection();
        $collection->where(['status' => 1]);
        $result = [];
        foreach ($collection as $item) {
            $result[$item['id']] = $item['code'];
        }
        return $result;
    }
}
