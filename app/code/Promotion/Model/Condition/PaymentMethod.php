<?php

namespace Redseanet\Promotion\Model\Condition;

class PaymentMethod implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'payment_method') {
            $method = $model->offsetGet('payment_method');
            switch ($condition['operator']) {
                case '=':
                    return $method === $condition['value'];
                case '<>':
                case '!=':
                    return $method !== $condition['value'];
            }
        }
        return false;
    }
}
