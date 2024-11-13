<?php

namespace Redseanet\Cms\Source;

use Redseanet\Cms\Model\Collection\Page as Collection;
use Redseanet\Lib\Source\SourceInterface;
use Laminas\Db\Sql\Predicate\NotIn;

class Page implements SourceInterface
{
    public function getSourceArray($except = [])
    {
        $collection = new Collection();
        if (!empty($except)) {
            $collection->where(new NotIn('cms_page.id', (array) $except));
        }
        $result = [];
        foreach ($collection as $page) {
            $result[$page['id']] = $page['title'];
        }
        return $result;
    }
}
