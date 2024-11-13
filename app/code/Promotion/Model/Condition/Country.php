<?php

namespace Redseanet\Promotion\Model\Condition;

class Country implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'country' && $address = $model->getShippingAddress()) {
            $value = $address->offsetGet('country');
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
