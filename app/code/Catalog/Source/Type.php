<?php

namespace Redseanet\Catalog\Source;

use Redseanet\Catalog\Model\Collection\Product\Type as Collection;
use Redseanet\Lib\Source\SourceInterface;

class Type implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\Translate;

    public function getSourceArray()
    {
        $collection = new Collection();
        $collection->columns(['id', 'name']);
        $result = [];
        foreach ($collection as $item) {
            $result[$item['id']] = $this->translate($item['name'], [], 'catalog');
        }
        return $result;
    }
}
