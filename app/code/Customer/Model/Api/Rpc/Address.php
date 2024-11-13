<?php

namespace Redseanet\Customer\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Customer\Model\Collection\Address as Collection;
use Redseanet\Customer\Model\Address as Model;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Model\Eav\Attribute\Set;
use Redseanet\Customer\Model\Customer;
use Redseanet\I18n\Model\Locate;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Language;

class Address extends AbstractHandler
{
    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function addressList($id, $token, $customerId, $languageId = 0)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $language = Bootstrap::getLanguage();
            $languageId = $language->getId();
        } else {
            $language = new Language();
            $language->load($languageId);
        }
        $collection = new Collection();
        $collection->where(['customer_id' => $customerId]);
        $collection->load(true, true);
        $resultData = [];
        $locate = new Locate();
        foreach ($collection as $item) {
            if (isset($item['country']) && $item['country'] != '' && is_numeric($item['country'])) {
                $label = $locate->getLabel('country', $item['country']);
                if (count($label)) {
                    $item['country_name'] = $label[$item['country']]->getName($language->offsetGet('code'));
                }
            } else {
                $item['country_name'] = $item['country'];
            }
            if (isset($item['region']) && $item['region'] != '' && is_numeric($item['region'])) {
                $label = $locate->getLabel('region', $item['region'], $item['country']);
                if (count($label)) {
                    $item['region_name'] = $label[$item['region']]->getName($language->offsetGet('code'));
                }
            } else {
                $item['region_name'] = $item['region'];
            }
            if (isset($item['city']) && $item['city'] != '' && is_numeric($item['city'])) {
                $label = $locate->getLabel('city', $item['city'], $item['region']);
                if (count($label)) {
                    $item['city_name'] = $label[$item['city']]->getName($language->offsetGet('code'));
                }
            } else {
                $item['city_name'] = $item['city'];
            }
            if (isset($item['county']) && $item['county'] != '' && is_numeric($item['county'])) {
                $label = $locate->getLabel('county', $item['county'], $item['county']);
                if (count($label)) {
                    $item['county_name'] = $label[$item['county']]->getName($language->offsetGet('code'));
                }
            } else {
                $item['county_name'] = $item['county'];
            }
            $resultData[] = $item;
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get cart information successfully'];
        return $this->responseData;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param int $addressId
     * @return object
     */
    public function addressInfo($id, $token, $customerId, $addressId)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $model = new Model();
        $model->load($addressId);
        if ($model->offsetGet('customer_id') != $customerId) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'you do not have permission to get the customer address information'];
            return $this->responseData;
        }
        $resultData = $model->toArray();
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get address information successfully'];
        return $this->responseData;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param object $data
     * @return bool
     */
    public function addressSave($id, $token, $customerId, $data)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $model = new Model();
        try {
            $data = (array) $data;
            if (!empty($data['id'])) {
                $model->load($data['id']);
                if ($model->offsetGet('customer_id') != $customerId) {
                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'you do not have permission to add the customer address information'];
                    return $this->responseData;
                }
            } else {
                $customer = new Customer();
                $customer->load($customerId);
                $data['store_id'] = $customer->offsetGet('store_id');
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
            $this->responseData = ['statusCode' => '200', 'data' => $model->toArray(), 'message' => 'add the address sccussfully'];
            return $this->responseData;
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'add the address failure' . $e];
            return $this->responseData;
        }
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param int $addressId
     * @return bool
     */
    public function addressDelete($id, $token, $customerId, $addressId)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $model = new Model();
        try {
            $model->load($addressId);
            if ($model->offsetGet('customer_id') == $customerId) {
                $model->remove();
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'delete the address sccussfully'];
                return $this->responseData;
            } else {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'delete the address failure'];
                return $this->responseData;
            }
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'delete the address failure' . $e];
            return $this->responseData;
        }
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function getDefaultAddress($id, $token, $customerId)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $collection = new Collection();
        $collection->where(['customer_id' => $customerId, 'is_default' => 1]);
        $collection->load(true, true);
        $resultData = [];
        foreach ($collection as $item) {
            $resultData = $item;
            break;
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get cart information successfully'];
        return $this->responseData;
    }
}
