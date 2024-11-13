<?php

namespace Redseanet\Lib\Model;

use NumberFormatter;
use Exception;
use Laminas\Db\Sql\Where;

class Language extends AbstractModel
{
    protected $numberFormatter = null;

    protected function construct()
    {
        $this->init('core_language', 'id', ['id', 'merchant_id', 'code', 'name', 'status', 'is_default']);
    }

    public function getMerchant()
    {
        if ($this->isLoaded) {
            $merchant = new Merchant();
            $merchant->load($this->offsetGet('merchant_id'));
            return $merchant;
        }
        return null;
    }

    public function formatNumber($number)
    {
        if (is_null($this->numberFormatter)) {
            $this->numberFormatter = new NumberFormatter($this->storage['code'], NumberFormatter::DECIMAL);
        }
        return $this->numberFormatter->format($number);
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
            $where->notEqualTo('id', $this->getId())
                    ->equalTo('is_default', 1)
                    ->equalTo('merchant_id', $this->storage['merchant_id']);
            $this->update(['is_default' => 0], $where);
            $this->getCacheObject()->delete($this->getCacheKey(), 'DATA_');
        }
        $this->flushList('core_merchant');
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
                    ->where->notEqualTo('id', $this->getId())
                    ->equalTo('merchant_id', $this->storage['merchant_id']);
            $result = $this->tableGateway->selectWith($select)->toArray();
            if (count($result)) {
                $this->update(['is_default' => 1], ['id' => $result[0]['id']]);
            } else {
                $this->rollback();
                throw new Exception('There must be one language record at least.');
            }
        }
        parent::beforeRemove();
    }

    protected function afterRemove()
    {
        $this->flushList('core_merchant');
        parent::afterRemove();
        $this->commit();
    }
}
