<?php

namespace Redseanet\Promotion\Model\Handler;

class Combination implements HandlerInterface
{
    public function matchItems($items, $handler)
    {
        if ($handler['operator'] === 'and') {
            $result = $items;
            foreach ($handler->getChildren() as $child) {
                $class = $child->getHandlerClass();
                if ($class) {
                    $result = $handler['value'] ? array_intersect_key($result, $class->matchItems($items, $child)) : array_diff_key($result, $class->matchItems($items, $child));
                    if (empty($result)) {
                        break;
                    }
                }
            }
        } else {
            $result = [];
            foreach ($handler->getChildren() as $child) {
                $class = $child->getHandlerClass();
                if ($class) {
                    $result += $handler['value'] ? $class->matchItems($items, $child) : array_diff_key($items, $class->matchItems($items, $child));
                }
            }
        }
        return $result;
    }
}
