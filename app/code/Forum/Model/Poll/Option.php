<?php

namespace Redseanet\Forum\Model\Poll;

use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Model\Increment;

class Option extends AbstractModel
{
    protected function construct()
    {
        $this->init('forum_poll_option', 'id', ['id', 'poll_id', 'description', 'sort_order']);
    }

    public function getVoters()
    {
        if ($this->getId()) {
            $customers = new Customer();
            $customers->join('forum_poll_voter', 'forum_poll_voter.customer_id=id', [], 'left')
                    ->where(['forum_poll_voter.option_id' => $this->getId()]);
            return $customers;
        }
        return [];
    }

    protected function beforeSave()
    {
        $this->storage['description'] = htmlspecialchars($this->storage['description'], ENT_QUOTES | ENT_HTML5);
        parent::beforeSave();
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        $result = parent::isUpdate($constraint, $insertForce);
        if (!$result && !$this->getId()) {
            $increment = new Increment();
            $increment->load($this->tableName);
            $this->setId($increment->getIncrementId());
        }
        return $result;
    }
}
