<?php

namespace Redseanet\Balance\Traits;

use Redseanet\Customer\Model\Collection\Balance as Collection;
use Laminas\Db\Sql\Expression;

trait Calc
{
    protected function getBalances($model)
    {
        $collection = new Collection();
        $collection->columns(['amount' => new Expression('sum(amount)')])
                ->where([
                    'customer_id' => $model->offsetGet('customer_id'),
                    'status' => 1
                ]);
        $collection->load(false, true);
        $balance = (count($collection) ? $collection[0]['amount'] : 0);
        return $balance;
    }
}
