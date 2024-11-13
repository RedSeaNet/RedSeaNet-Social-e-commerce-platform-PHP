<?php

namespace Redseanet\RewardPoints\Traits;

use Laminas\Db\Sql\Expression;

trait Retrench
{
    use \Redseanet\Lib\Traits\DB;

    public function process()
    {
        $adapter = $this->getContainer()->get('dbAdapter');
        $adapter->query('CREATE TEMPORARY TABLE tmp_reward_points(customer_id integer,count decimal(12,4));', $adapter::QUERY_MODE_EXECUTE);
        $tableGateway = $this->getTableGateway('reward_points');
        $select = $tableGateway->getSql()->select();
        $select->columns(['customer_id', 'count' => new Expression('sum(count)')])
                ->where(['status' => 1])
                ->group('customer_id');
        $tmpTableGateway = $this->getTableGateway('tmp_reward_points');
        $tmpInsert = $tmpTableGateway->getSql()->insert();
        $tmpInsert->values($select);
        $tmpTableGateway->insertWith($tmpInsert);
        $adapter->query('TRUNCATE reward_points;', $adapter::QUERY_MODE_EXECUTE);
        $tmpSelect = $tmpTableGateway->getSql()->select();
        $tmpSelect->columns(['customer_id', 'count']);
        $insert = $tableGateway->getSql()->insert();
        $insert->columns(['customer_id', 'count'])->values($tmpSelect);
        $tableGateway->insertWith($insert);
        $adapter->query('DROP TABLE tmp_reward_points;', $adapter::QUERY_MODE_EXECUTE);
    }
}
