<?php

namespace Redseanet\Promotion\Model\Condition;

use Laminas\Db\Sql\Predicate\Operator;

class CustomerGroup implements ConditionInterface
{
    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'customer_group') {
            if ($condition['value'] === '0') {
                return !$model->offsetGet('customer_id');
            }
            $tableGateway = $this->getTableGateway('customer_in_group');
            $select = $tableGateway->getSql()->select();
            $select->where(new Operator('group_id', preg_replace('/[^\<\>\=\!]/', '', $condition['operator']), $condition['value']))
                    ->where(['customer_id' => $model->offsetGet('customer_id')]);
            return (bool) count($tableGateway->selectWith($select)->toArray());
        }
        return false;
    }
}
