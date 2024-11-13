<?php

namespace Redseanet\Customer\Model\Api\Soap;

use Exception;
use Redseanet\Api\Model\Api\AbstractHandler;
use Redseanet\Customer\Model\Collection\Address as Collection;
use Redseanet\Customer\Model\Address as Model;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Model\Eav\Attribute\Set;

class Address extends AbstractHandler
{
    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function addressList($sessionId, $customerId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $collection = new Collection();
        $collection->where(['customer_id' => $customerId]);
        $collection->load(true, true);
        $result = [];
        foreach ($collection as $item) {
            $result[] = (object) $item;
        }
        return $result;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param int $addressId
     * @return object
     */
    public function addressInfo($sessionId, $customerId, $addressId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $model = new Model();
        $model->load($addressId);
        if ($model->offsetGet('customer_id') != $customerId) {
            throw new Exception('Not Allowed');
        }
        return $this->response($model->toArray());
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param object $data
     * @return bool
     */
    public function addressSave($sessionId, $customerId, $data)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $model = new Model();
        try {
            $data = (array) $data;
            if (!empty($data['id'])) {
                $model->load($data['id']);
                if ($model->offsetGet('customer_id') != $customerId) {
                    throw new Exception('Not Allowed');
                }
            } else {
                $customer = new Customer();
                $customer->load($customerId);
                $data['store_id'] = $customer->offsetGet('customer_id');
                $data['status'] = 1;
                $data['customer_id'] = $customerId;
                $type = new Type();
                $type->load(Model::ENTITY_TYPE, 'code');
                $data['type_id'] = $type->getId();
                $set = new Set();
                $set->load($type->getId(), 'type_id');
                $data['attribute_set_id'] = $set->getId();
            }
            $model->setData($data)->save();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param int $addressId
     * @return bool
     */
    public function addressDelete($sessionId, $customerId, $addressId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $model = new Model();
        try {
            $model->load($addressId);
            if ($model->offsetGet('customer_id') == $customerId) {
                $model->remove();
                return true;
            }
        } catch (Exception $e) {
        }
        return false;
    }
}
