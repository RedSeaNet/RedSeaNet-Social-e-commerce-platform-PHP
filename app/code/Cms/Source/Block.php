<?php

namespace Redseanet\Cms\Source;

use Redseanet\Cms\Model\Collection\Block as Collection;
use Redseanet\Lib\Source\SourceInterface;
use Laminas\Db\Sql\Predicate\NotIn;

class Block implements SourceInterface
{
    public function getSourceArray($except = [])
    {
        $collection = new Collection();
        if (!empty($except)) {
            $collection->where(new NotIn('cms_block.id', (array) $except));
        }
        $result = [];
        foreach ($collection as $page) {
            $result[$page['id']] = $page['code'];
        }
        return $result;
    }
}
