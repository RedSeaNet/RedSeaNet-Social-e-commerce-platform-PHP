<?php

namespace Redseanet\Promotion\Model\Condition;

class Combination implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['operator'] === 'and') {
            $result = true;
            foreach ($condition->getChildren() as $child) {
                $class = $child->getConditionClass();
                if (!$class || !$class->match($model, $child, $storeId)) {
                    $result = false;
                    break;
                }
            }
        } else {
            $result = false;
            foreach ($condition->getChildren() as $child) {
                $class = $child->getConditionClass();
                if ($class && $class->match($model, $child, $storeId)) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }
}
