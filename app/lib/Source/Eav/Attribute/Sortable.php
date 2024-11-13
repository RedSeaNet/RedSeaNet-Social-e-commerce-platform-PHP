<?php

namespace Redseanet\Lib\Source\Eav\Attribute;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Eav\Attribute as Collection;
use Redseanet\Lib\Source\SourceInterface;

class Sortable implements SourceInterface
{
    protected $entityType = '';

    public function getSourceArray()
    {
        $collection = new Collection();
        $collection->columns(['code']);
        if ($this->entityType) {
            $collection->withLabel(Bootstrap::getLanguage()->getId())
                    ->join('eav_entity_type', 'eav_entity_type.id=type_id', [], 'right')
                    ->where(['sortable' => 1, 'eav_entity_type.code' => $this->entityType]);
        }
        $result = [];
        foreach ($collection as $item) {
            $result[$item['code']] = $item['label'];
        }
        return $result;
    }
}
