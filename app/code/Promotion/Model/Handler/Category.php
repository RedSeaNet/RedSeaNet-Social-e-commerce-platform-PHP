<?php

namespace Redseanet\Promotion\Model\Handler;

use Laminas\Db\Sql\Predicate\Operator;

class Category implements HandlerInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function matchItems($items, $handler)
    {
        $result = [];
        if ($handler['identifier'] === 'category') {
            $tableGateway = $this->getTableGateway('product_in_category');
            $select = $tableGateway->getSql()->select();
            $select->where(new Operator('category_id', preg_replace('/[^\<\>\=\!]/', '', $handler['operator']), $handler['value']));
            $where = '(';
            foreach ($items as $item) {
                $where .= 'product_id = ' . $item['product_id'] . ' OR';
            }
            $select->where(preg_replace('/OR\s*$/', ')', $where));
            $set = $tableGateway->selectWith($select)->toArray();
            foreach ($set as $item) {
                $result[$item['product_id']] = $items[$item['product_id']];
            }
        }
        return $result;
    }
}
