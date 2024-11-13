<?php

namespace Redseanet\Email\Source;

use Redseanet\Email\Model\Collection\Template as Collection;
use Redseanet\Lib\Source\SourceInterface;

class Template implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Collection();
        $collection->columns(['code', 'subject']);
        $result = [];
        foreach ($collection as $item) {
            $result[$item['code']] = $item['subject'];
        }
        return $result;
    }
}
