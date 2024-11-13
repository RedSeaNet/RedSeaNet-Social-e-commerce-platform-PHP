<?php

namespace Redseanet\Promotion\Model\Condition;

class AttributeSet implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'attribute_set') {
            switch ($condition['operator']) {
                case '=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && $item['product']['attribute_set_id'] == $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && $item['product']['attribute_set_id'] != $condition['value']) {
                            return true;
                        }
                    }
                    break;
            }
        }
        return false;
    }
}
