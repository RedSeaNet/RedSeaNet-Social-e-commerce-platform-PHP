<?php

namespace Redseanet\Promotion\Model\Condition;

use Laminas\Db\Sql\Predicate\Operator;

class Category implements ConditionInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function match($model, $condition, $storeId)
    {
        if ($condition['identifier'] === 'category') {
            $tableGateway = $this->getTableGateway('product_in_category');
            $select = $tableGateway->getSql()->select();
            $select->where(new Operator('category_id', preg_replace('/[^\<\>\=\!]/', '', $condition['operator']), $condition['value']));
            $where = '(';
            foreach ($model->getItems(true) as $item) {
                if ((!isset($item['store_id']) || $item['store_id'] == $storeId)) {
                    $where .= 'product_id = ' . $item['product_id'] . ' OR';
                }
            }
            $select->where(preg_replace('/OR\s*$/', ')', $where));
            return (bool) count($tableGateway->selectWith($select)->toArray());
        }
        return false;
    }
}
