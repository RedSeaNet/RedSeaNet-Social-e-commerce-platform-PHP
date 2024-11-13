<?php

namespace Redseanet\Promotion\Model\Condition;

class TotalWeight implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'total_weight') {
            $weight = (float) $model->getQty($storeId);
            switch ($condition['operator']) {
                case '=':
                    return $weight === (float) $condition['value'];
                case '<>':
                case '!=':
                    return $weight !== (float) $condition['value'];
                case '>':
                    return $weight > (float) $condition['value'];
                case '>=':
                    return $weight >= (float) $condition['value'];
                case '<':
                    return $weight < (float) $condition['value'];
                case '<=':
                    return $weight <= (float) $condition['value'];
                case 'in':
                    return in_array($weight, explode(',', $condition['value']));
                case 'not in':
                case 'nin':
                    return !in_array($weight, explode(',', $condition['value']));
            }
        }
        return false;
    }
}
