<?php

namespace Redseanet\Promotion\Model\Condition;

use Redseanet\Customer\Model\Customer;

class CustomerId implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'customer_id') {
            $value = new Customer();
            $value->load($model['customer_id']);
            if ($value = (int) $value->getId()) {
                switch ($condition['operator']) {
                    case '=':
                        return $value === (int) $condition['value'];
                    case '<>':
                    case '!=':
                        return $value !== (int) $condition['value'];
                }
            }
        }
        return false;
    }
}
