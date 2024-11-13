<?php

namespace Redseanet\Forum\Model;

use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Select;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Model\Increment;

class Poll extends AbstractModel
{
    protected function construct()
    {
        $this->init('forum_poll', 'id', ['id', 'title', 'max_choices', 'expired_at']);
    }

    public function getOptions()
    {
        if ($this->getId()) {
            $options = new Collection\Poll\Option();
            $options->where(['poll_id' => $this->getId()])
                    ->order('sort_order ASC');
            return $options;
        }
        return [];
    }

    public function getOptionsByCustomer()
    {
        if ($this->getId()) {
            $options = new Collection\Poll\Option();
            $options->columns(['id'])
                    ->join('forum_poll_voter', 'forum_poll_voter.option_id=forum_poll_option.id', [], 'left')
                    ->where([
                        'forum_poll_voter.poll_id' => $this->getId(),
                        'forum_poll_voter.customer_id' => (new Segment('customer'))->get('customer')['id']
                    ]);
            $options->load(true, false);
            $result = [];
            $options->walk(function ($item) use (&$result) {
                $result[] = $item['id'];
            });
            return $result;
        }
        return [];
    }

    public function getVoters()
    {
        if ($this->getId()) {
            $customers = new Customer();
            $subselect = new Select('forum_poll_voter');
            $subselect->columns(['customer_id'])->where(['poll_id' => $this->getId()]);
            $customers->in('id', $subselect);
            return $customers;
        }
        return [];
    }

    public function voted()
    {
        $segment = new Segment('customer');
        if ($this->getId() && $segment->get('hasLoggedIn')) {
            return (bool) count($this->getTableGateway('forum_poll_voter')->select([
                'poll_id' => $this->getId(),
                'customer_id' => $segment->get('customer')['id']
            ])->toArray());
        }
        return true;
    }

    public function vote($options)
    {
        $segment = new Segment('customer');
        if ($this->getId() && $segment->get('hasLoggedIn') && $this->storage['max_choices'] <= count((array) $options)) {
            $data = [
                'poll_id' => $this->getId(),
                'customer_id' => $segment->get('customer')['id']
            ];
            foreach ((array) $options as $option) {
                $this->getTableGateway('forum_poll_voter')->insert($data + ['option_id' => $option]);
            }
            $this->flushList(Customer::ENTITY_TYPE);
            $this->flushList($this->getCacheKey());
            return true;
        }
        return false;
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
