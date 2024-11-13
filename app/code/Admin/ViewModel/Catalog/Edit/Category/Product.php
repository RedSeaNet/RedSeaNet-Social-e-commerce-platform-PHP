<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Category;

use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Catalog\Model\Category as Model;
use Redseanet\Lib\ViewModel\Template;
use Laminas\Db\Sql\Select;

class Product extends Template
{
    use \Redseanet\Lib\Traits\Filter;

    use \Redseanet\Lib\Traits\DB;

    protected $activeIds = null;

    public function getCollection()
    {
        $collection = new Collection();
        $query = $this->getQuery();
        if (!isset($query['asc']) && !isset($query['desc'])) {
            $query['asc'] = 'id';
        }
        $this->filter($collection, array_intersect_key($query, ['asc' => 1, 'desc' => 1, 'limit' => 1, 'page' => 1, 'id' => 1, 'name' => 1, 'sku' => 1]));
        return $collection;
    }

    public function getSortOrder($collection)
    {
        $result = [];
        $tmpselect = clone ($collection->getSelect());
        $tmpselect->columns(['id']);
        $subselect = new Select(['tmp' => $tmpselect]);
        $subselect->columns(['id']);
        $tableGateway = $this->getTableGateway('product_in_category');
        $select = $tableGateway->getSql()->select();
        $select->where(['category_id' => $this->getQuery('cid')])
                ->where->in('product_id', $subselect);
        foreach ($tableGateway->selectWith($select)->toArray() as $item) {
            $result[$item['product_id']] = $item['sort_order'];
        }
        return $result;
    }

    public function getActiveIds()
    {
        if (is_null($this->activeIds)) {
            $collection = (new Model())->setId($this->getQuery('cid'))
                    ->getProducts();
            $this->activeIds = [];
            if (is_object($collection)) {
                $collection->walk(function ($item) {
                    $this->activeIds[] = $item['id'];
                });
            }
        }
        return $this->activeIds;
    }
}
