<?php

namespace Redseanet\Forum\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Bootstrap;
use Redseanet\Customer\Model\Customer;

class Blogger extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;

    public function getBloggerInfo($id, $token, $customerId, $conditionData = [], $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $customer = new Customer();
        $customer->load($customerId);
        if ($customer->getId()) {
            $resultData = ['id' => $customer->getId()];
            $attributes = new Attribute();
            $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                    ->where(['eav_entity_type.code' => Customer::ENTITY_TYPE])
            ->where->notEqualTo('input', 'password');
            $attributes->load(true, true);
            $attributes->walk(function ($attribute) use (&$resultData, $customer) {
                $resultData[$attribute['code']] = $customer->offsetGet($attribute['code']);
            });
            $resultData['increment_id'] = $customer->offsetGet('increment_id');
            if (isset($resultData['avatar']) && $resultData['avatar'] != '') {
                $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $resultData['avatar']);
                unset($resultData['avatar']);
            } else {
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
            }
            $resultData['avatar'] = $avatar;
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get blogger information successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate('get blogger information unsuccessfully', [], null, $language['code'])];
            return $this->responseData;
        }
    }

}
