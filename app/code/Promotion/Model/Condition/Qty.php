<?php

namespace Redseanet\Promotion\Model\Condition;

class Qty implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'qty') {
            switch ($condition['operator']) {
                case '=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && (float) $item['qty'] === (float) $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && (float) $item['qty'] !== (float) $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case '>':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && (float) $item['qty'] > (float) $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case '>=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && (float) $item['qty'] >= (float) $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case '<':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && (float) $item['qty'] < (float) $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case '<=':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && (float) $item['qty'] <= (float) $condition['value']) {
                            return true;
                        }
                    }
                    break;
                case 'in':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && in_array((float) $item['qty'], explode(',', $condition['value']))) {
                            return true;
                        }
                    }
                    break;
                case 'not in':
                case 'nin':
                    foreach ($model->getItems(true) as $item) {
                        if ((!isset($item['store_id']) || $item['store_id'] == $storeId) && !in_array((float) $item['qty'], explode(',', $condition['value']))) {
                            return true;
                        }
                    }
                    break;
            }
        }
        return false;
    }
}
