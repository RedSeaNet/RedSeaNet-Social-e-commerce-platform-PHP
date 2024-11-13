<?php

namespace Redseanet\Customer\Model;

use Redseanet\Lib\Model\AbstractModel;

class Persistent extends AbstractModel
{
    protected function construct()
    {
        $this->init('persistent', 'customer_id', ['key', 'customer_id']);
    }

    public function save($constraint = [], $insertForce = false)
    {
        try {
            if (empty($constraint)) {
                $constraint = [$this->primaryKey => $this->getId()];
            }
            $this->beforeSave();
            $columns = $this->prepareColumns();
            if ($columns) {
                $this->upsert($columns, $constraint);
            }
            $this->isNew = false;
            $this->afterSave();
            $id = array_values($constraint)[0];
            $key = array_keys($constraint)[0];
            $this->flushRow($id, null, $this->getCacheKey(), $key === $this->primaryKey ? null : $key);
            $this->flushList($this->getCacheKey());
        } catch (InvalidQueryException $e) {
            $this->getContainer()->get('log')->logException($e);
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
        }
        return $this;
    }
}
