<?php

namespace Redseanet\Promotion\Model\Condition;

class Subtotal implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'subtotal') {
            $subtotal = 0;
            foreach ($model->getItems(true) as $item) {
                if (isset($item['store_id']) && $item['store_id'] == $storeId) {
                    $subtotal += (float) $item['base_total'];
                }
            }
            switch ($condition['operator']) {
                case '=':
                    return $subtotal === (float) $condition['value'];
                case '<>':
                case '!=':
                    return $subtotal !== (float) $condition['value'];
                case '>':
                    return $subtotal > (float) $condition['value'];
                case '>=':
                    return $subtotal >= (float) $condition['value'];
                case '<':
                    return $subtotal < (float) $condition['value'];
                case '<=':
                    return $subtotal <= (float) $condition['value'];
                case 'in':
                    return in_array($subtotal, explode(',', $condition['value']));
                case 'not in':
                case 'nin':
                    return !in_array($subtotal, explode(',', $condition['value']));
            }
        }
        return false;
    }
}
