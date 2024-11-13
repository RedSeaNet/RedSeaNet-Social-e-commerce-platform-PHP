<?php

namespace Redseanet\Bulk\Model;

use Redseanet\Bulk\Exception\FullBulkException;
use Redseanet\Bulk\Model\Collection\Bulk\Item as ItemCollection;
use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Increment;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class Bulk extends AbstractModel
{
    protected $items = null;

    protected function construct()
    {
        $this->init('bulk_sale', 'id', ['id', 'customer_id', 'customer_name', 'size', 'count', 'description', 'status']);
    }

    public function getOrderIds()
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('bulk_sale_member');
            $select = $tableGateway->getSql()->select();
            $select->columns(['order_id'])->where(['bulk_id' => $this->getId()]);
            $resultSet = $tableGateway->selectWith($select)->toArray();
            $result = [];
            foreach ($resultSet as $item) {
                $result[] = $item['order_id'];
            }
            return $result;
        }
        return [];
    }

    public function getOrderId()
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('bulk_sale_member');
            $select = $tableGateway->getSql()->select();
            $select->columns(['order_id'])->where(['bulk_id' => $this->getId(), 'member_id' => (new Segment('customer'))->get('customer')['id']]);
            $resultSet = $tableGateway->selectWith($select)->toArray();
            if (isset($resultSet[0]['order_id'])) {
                return $resultSet[0]['order_id'];
            }
        }
        return null;
    }

    public function canShip()
    {
        if (!$this->getId() || empty($this->storage['status'])) {
            return false;
        }
        $config = $this->getContainer()->get('config');
        if ($config['catalog/bulk_sale/limitation']) {
            return $this->storage['size'] <= $this->storage['count'];
        } else {
            $expiration = [$config['catalog/bulk_sale/default_expiration']];
            foreach ($this->getItems() as $item) {
                if (!empty($item['product']['bulk_expiration'])) {
                    $expiration[] = (int) $item['product']['bulk_expiration'];
                }
            }
            return strtotime($this->storage['created_at']) <= strtotime('-' . min($expiration) . ' days');
        }
    }

    public function getMembers()
    {
        if ($this->getId()) {
            $members = new Customer();
            $select = new Select('bulk_sale_member');
            $select->columns(['member_id'])->where(['bulk_id' => $this->getId()]);
            $members->in('id', $select);
            return $members;
        }
        return [];
    }

    public function hasMember($customer)
    {
        $members = $this->getMembers();
        if (!is_array($members)) {
            $members->where(['id' => $customer]);
        }
        return (bool) count($members);
    }

    public function addMember($customer, $orderId)
    {
        if ($this->getId()) {
            if (count($this->getMembers()) >= $this->storage['size']) {
                throw new FullBulkException();
            }
            $this->getTableGateway('bulk_sale_member')
                    ->insert([
                        'bulk_id' => $this->getId(),
                        'member_id' => is_scalar($customer) ? $customer : $customer['id'],
                        'order_id' => $orderId
                    ]);
            $select = new Select('bulk_sale_member');
            $select->columns(['count' => new Expression('count(1)')])->where(['bulk_id' => $this->getId()]);
            $this->setData('count', $select)->save();
            return true;
        }
        return false;
    }

    public function delMember($customer)
    {
        if ($this->getId()) {
            $result = (bool) $this->getTableGateway('bulk_sale_member')
                            ->delete([
                                'bulk_id' => $this->getId(),
                                'member_id' => is_scalar($customer) ? $customer : $customer['id']
                            ]);
            $this->setData('count', ((int) $this->offsetGet('count')) - (is_array($customer) ? count($customer) : 1))->save();
            return $result;
        }
        return false;
    }

    public function getItems($force = false)
    {
        if ($force || is_null($this->items)) {
            $items = new ItemCollection();
            $items->where(['bulk_id' => $this->getId()]);
            $result = [];
            $items->walk(function ($item) use (&$result) {
                $result[$item['id']] = $item;
            });
            $this->items = $result;
            if ($force) {
                return $items;
            }
        }
        return $this->items;
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
