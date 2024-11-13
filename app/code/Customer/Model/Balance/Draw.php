<?php

namespace Redseanet\Customer\Model\Balance;

use Exception;
use Redseanet\Customer\Model\Balance as Record;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Laminas\Db\Sql\Select;

class Draw extends AbstractModel
{
    protected function construct()
    {
        $this->init('customer_balance_draw_application', 'id', ['id', 'customer_id', 'account_id', 'account', 'type', 'amount', 'status']);
    }

    public function getCustomer()
    {
        if (!empty($this->storage['customer_id'])) {
            $customer = new Customer();
            $customer->load($this->storage['customer_id']);
            return $customer;
        }
        return null;
    }

    protected function beforeSave()
    {
        $isNew = $this->isNew && !$this->getId();
        if ($isNew && (empty($this->storage['amount']) || $this->getCustomer()->getBalance() < $this->storage['amount'])) {
            throw new Exception('Invalid amount to draw');
        } elseif ($isNew || !empty($this->storage['account_id']) && empty($this->storage['account'])) {
            $amount = new Select('customer_balance_draw_account');
            $amount->columns(['detail'])
                    ->where(['id' => $this->storage['account_id']]);
            $type = new Select('customer_balance_draw_account');
            $type->columns(['type'])
                    ->where(['id' => $this->storage['account_id']]);
            $this->setData([
                'account' => $amount,
                'type' => $type
            ]);
        }
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if ($this->isNew && !empty($this->storage['amount'])) {
            $balance = new Record();
            $balance->setData([
                'customer_id' => $this->storage['customer_id'],
                'amount' => 0 - $this->storage['amount'],
                'comment' => 'Draw',
                'additional' => $this->getId(),
                'status' => 1
            ])->save();
        } elseif ($this->offsetGet('status') == -1) {
            if (empty($this->storage['amount'])) {
                $this->load($this->getId());
            }
            $balance = new Record();
            $balance->setData([
                'customer_id' => $this->storage['customer_id'],
                'amount' => $this->storage['amount'],
                'comment' => 'Cancel Draw',
                'additional' => $this->getId(),
                'status' => 1
            ])->save();
        }
        parent::afterSave();
        $this->commit();
    }
}
