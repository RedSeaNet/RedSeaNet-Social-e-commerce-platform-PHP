<?php

namespace Redseanet\Retailer\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Retailer\Model\Retailer;

class Customer implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    public function beforeRemove($e)
    {
        $model = new Retailer();
        $model->load($e['model']->getId(), 'customer_id');
        if ($model->getId()) {
            $tableGateway = $this->getTableGateway('retailer_manager');
            $select = $tableGateway->getSql()->select();
            $select->where(['retailer_id' => $model->getId()])->where->notEqualTo('customer_id', $e['model']->getId());
            if (!count($tableGateway->selectWith($select)->toArray())) {
                $this->getTableGateway('product_entity')->update(['status' => 0], ['store_id' => $model['store_id']]);
                $this->getContainer()->get('indexer')->reindex('product');
                $this->flushList('product');
                $model->remove();
            }
        }
    }
}
