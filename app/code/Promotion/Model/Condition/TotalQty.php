<?php

namespace Redseanet\Promotion\Model\Condition;

class TotalQty implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'total_qty') {
            $value = (float) $model->getQty($storeId);
            switch ($condition['operator']) {
                case '=':
                    return $value === (float) $condition['value'];
                case '<>':
                case '!=':
                    return $value !== (float) $condition['value'];
                case '>':
                    return $value > (float) $condition['value'];
                case '>=':
                    return $value >= (float) $condition['value'];
                case '<':
                    return $value < (float) $condition['value'];
                case '<=':
                    return $value <= (float) $condition['value'];
                case 'in':
                    return in_array($value, explode(',', $condition['value']));
                case 'not in':
                case 'nin':
                    return !in_array($value, explode(',', $condition['value']));
            }
        }
        return false;
    }
}
