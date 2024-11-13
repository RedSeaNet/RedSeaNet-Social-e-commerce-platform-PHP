<?php

namespace Redseanet\Promotion\Model\Condition;

class ProductType implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'product_type') {
            switch ($condition['operator']) {
                case '=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && $item['product']['product_type_id'] == $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && $item['product']['product_type_id'] != $condition['value']) {
                            return true;
                        }
                    }
                    break;
            }
        }
        return false;
    }
}
