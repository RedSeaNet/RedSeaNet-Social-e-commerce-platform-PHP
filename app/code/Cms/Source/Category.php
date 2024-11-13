<?php

namespace Redseanet\Cms\Source;

use Redseanet\Cms\Model\Collection\Category as Collection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Source\SourceInterface;
use Laminas\Db\Sql\Predicate\NotIn;
use Redseanet\Lib\Tool\PHPTree;

class Category implements SourceInterface
{
    public function getSourceArray($except = [])
    {
        $collection = new Collection();
        if (!empty($except)) {
            $collection->where(new NotIn('id', (array) $except));
        }
        $result = [];
        $language = Bootstrap::getLanguage()->getId();
        foreach ($collection as $category) {
            $result[$category['id']] = $category['name'][$language];
        }
        return $result;
    }

    public function getSourceArrayTree($except = [])
    {
        $collection = new Collection();
        if (!empty($except)) {
            $collection->where(new NotIn('id', (array) $except));
        }
        $result = [];
        $result[] = ['id' => '', 'name' => 'Default Category', 'parent_id' => 0];
        $language = Bootstrap::getLanguage()->getId();
        foreach ($collection as $category) {
            $result[] = ['id' => $category['id'], 'name' => (isset($category['name'][$language]) && $category['name'][$language] != '') ? $category['name'][$language] : array_shift($category['name']), 'parent_id' => (isset($category['parent_id']) ? $category['parent_id'] : 0)];
        }
        return PHPTree::makeTreeForHtml($result);
    }
}
