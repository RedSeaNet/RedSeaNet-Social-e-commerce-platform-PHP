<?php

namespace Redseanet\Promotion\Model\Condition;

class Region implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'region' && $address = $model->getShippingAddress()) {
            $value = $address->offsetGet('region');
            switch ($condition['operator']) {
                case '=':
                    return $value === $condition['value'];
                case '<>':
                case '!=':
                    return $value !== $condition['value'];
            }
        }
        return false;
    }
}
