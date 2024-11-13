<?php

namespace Redseanet\Promotion\Model\Condition;

class City implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'city' && $address = $model->getShippingAddress()) {
            $value = $address->offsetGet('city');
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
