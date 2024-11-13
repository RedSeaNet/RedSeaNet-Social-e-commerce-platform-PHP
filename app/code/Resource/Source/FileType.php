<?php

namespace Redseanet\Resource\Source;

use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Resource\Model\Collection\Resource;

class FileType implements SourceInterface
{
    public function getSourceArray()
    {
        $collection = new Resource();
        $collection->columns(['file_type'])->group('file_type')->order('file_type ASC');
        $result = [];
        foreach ($collection as $item) {
            $result[$item['file_type']] = $item['file_type'];
        }
        return $result;
    }
}
