<?php

namespace Redseanet\Promotion\Model\Condition;

class County implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'county' && $address = $model->getShippingAddress()) {
            $value = $address->offsetGet('county');
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
