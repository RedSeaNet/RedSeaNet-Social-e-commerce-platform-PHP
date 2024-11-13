<?php

namespace Redseanet\Promotion\Model\Condition;

class ProductId implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'product_id') {
            switch ($condition['operator']) {
                case '=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && $item['product_id'] == $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && $item['product_id'] != $condition['value']) {
                            return true;
                        }
                    }
                    break;
            }
        }
        return false;
    }
}
