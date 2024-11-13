<?php

namespace Redseanet\Promotion\Model\Condition;

class ShippingMethod implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'shipping_method') {
            $value = $model->offsetGet('shipping_method');
            if (strpos($value, ':')) {
                $value = json_decode($value, true);
                if (isset($value[$storeId])) {
                    $value = $value[$storeId];
                } else {
                    return false;
                }
            }
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
