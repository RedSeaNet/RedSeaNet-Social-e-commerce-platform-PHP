<?php

namespace Redseanet\Lib\Model;

use Exception;
use Redseanet\Lib\Model\Collection;
use Laminas\Db\Sql\Where;

class Merchant extends AbstractModel
{
    protected function construct()
    {
        $this->init('core_merchant', 'id', ['id', 'code', 'name', 'is_default', 'status']);
    }

    public function getStore($code = null)
    {
        $store = new Collection\Store();
        if (!is_null($code)) {
            $store->where(['merchant_id' => $this->getId(), 'code' => $code, 'status' => 1]);
        } else {
            $store->where(['merchant_id' => $this->getId(), 'is_default' => 1, 'status' => 1]);
        }
        $store->load();
        return count($store) ? new Store($store[0]) : null;
    }

    public function getLanguage($code = null)
    {
        $lang = new Collection\Language();
        if (!is_null($code)) {
            $lang->where(['merchant_id' => $this->getId(), 'code' => $code, 'status' => 1]);
        } else {
            $lang->where(['merchant_id' => $this->getId(), 'is_default' => 1, 'status' => 1]);
        }
        $lang->load();
        return count($lang) ? new Language($lang[0]) : null;
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if ($this->storage['is_default']) {
            $where = new Where();
            $where->equalTo('is_default', 1)
                    ->notEqualTo('id', $this->getId());
            $this->update(['is_default' => 0], $where);
        }
        parent::afterSave();
        $this->commit();
    }

    protected function beforeRemove()
    {
        $this->beginTransaction();
        $this->load($this->getId());
        if ($this->storage['is_default']) {
            $select = $this->tableGateway->getSql()->select();
            $select->columns(['id'])->limit(1)
            ->where->notEqualTo('id', $this->getId());
            $result = $this->tableGateway->selectWith($select)->toArray();
            if (count($result)) {
                $this->update(['is_default' => 1], ['id' => $result[0]['id']]);
            } else {
                $this->rollback();
                throw new Exception('There must be one merchant record at least.');
            }
        }
        parent::beforeRemove();
    }

    protected function afterRemove()
    {
        parent::afterRemove();
        $this->commit();
    }
}
