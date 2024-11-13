<?php

namespace Redseanet\Promotion\Model\Condition;

class Postcode implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'postcode' && $address = $model->getShippingAddress()) {
            $value = $address->offsetGet('postcode');
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
