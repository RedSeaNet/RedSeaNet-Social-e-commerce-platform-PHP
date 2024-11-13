<?php

namespace Redseanet\Admin\ViewModel\Promotion\Edit;

use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Promotion\Model\Collection\Condition as Collection;

class Condition extends Edit
{
    public function getOptions($source)
    {
        if (is_subclass_of($source, '\\Redseanet\\Lib\\Source\\SourceInterface')) {
            return (new $source())->getSourceArray(true);
        }
        return [];
    }

    public function getCollection()
    {
        if ($this->getQuery('id')) {
            $collection = new Collection();
            $collection->where(['promotion_id' => $this->getQuery('id')]);
            $result = [];
            foreach ($collection as $item) {
                $pid = (int) $item->offsetGet('parent_id');
                if (!isset($result[$pid])) {
                    $result[$pid] = [];
                }
                $result[$pid][] = $item->toArray();
            }
            return $result;
        }
        return [];
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'qty' => [
                'type' => 'number',
                'label' => 'Maximum Qty Discount is Applied To',
                'attrs' => [
                    'min' => 0
                ],
                'comment' => 'Effective only when effective,Fill in the blank, fill 0 or 1 means that all meet the conditions of the goods only a discount, recommended to fill 1'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
