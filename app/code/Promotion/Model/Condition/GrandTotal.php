<?php

namespace Redseanet\Promotion\Model\Condition;

class GrandTotal implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'grand_total') {
            $subtotal = 0;
            foreach ($model->getItems(true) as $item) {
                if (isset($item['store_id']) && $item['store_id'] == $storeId) {
                    $subtotal += (float) $item['base_total'];
                }
            }
            $total = $subtotal + (float) $model['base_tax'] + (float) $model['base_shipping'];
            switch ($condition['operator']) {
                case '=':
                    return $total === (float) $condition['value'];
                case '<>':
                case '!=':
                    return $total !== (float) $condition['value'];
                case '>':
                    return $total > (float) $condition['value'];
                case '>=':
                    return $total >= (float) $condition['value'];
                case '<':
                    return $total < (float) $condition['value'];
                case '<=':
                    return $total <= (float) $condition['value'];
                case 'in':
                    return in_array($total, explode(',', $condition['value']));
                case 'not in':
                case 'nin':
                    return !in_array($total, explode(',', $condition['value']));
            }
        }
        return false;
    }
}
