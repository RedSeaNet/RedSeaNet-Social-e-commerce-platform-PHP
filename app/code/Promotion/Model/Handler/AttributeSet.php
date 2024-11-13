<?php

namespace Redseanet\Promotion\Model\Handler;

class AttributeSet implements HandlerInterface
{
    public function matchItems($items, $handler)
    {
        $result = [];
        if ($handler['identifier'] === 'attribute_set') {
            switch ($handler['operator']) {
                case '=':
                    foreach ($items as $id => $item) {
                        if ($item['product']['attribute_set_id'] == $condition['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($items as $id => $item) {
                        if ($item['product']['attribute_set_id'] != $condition['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
            }
        }
        return $result;
    }
}
