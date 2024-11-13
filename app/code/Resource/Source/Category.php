<?php

namespace Redseanet\Resource\Source;

use Redseanet\Resource\Model\Collection\Category as Collection;
use Redseanet\Lib\Source\SourceInterface;
use Laminas\Db\Sql\Predicate\NotIn;
use Redseanet\Lib\Bootstrap;

class Category implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function getSourceArray($except = [])
    {
        $collection = new Collection();
        if (!empty($except)) {
            $collection->where(new NotIn('resource_category.id', (array) $except));
        }
        $result = [];
        foreach ($collection as $category) {
            $result[$category['id']] = $category['name'][Bootstrap::getLanguage()->getId()] ?? ($category['name'][0] ?? '');
        }
        return $result;
    }
}
