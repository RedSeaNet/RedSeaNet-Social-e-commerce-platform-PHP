<?php

namespace Redseanet\Customer\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Customer\Exception\InvalidSmsCodeException;
use Redseanet\Customer\Model\Collection\Customer as Collection;
use Redseanet\Customer\Model\Customer as Model;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Oauth\Model\Api\Soap\Oauth;
use Laminas\Db\Sql\Where;
use Redseanet\Lib\Bootstrap;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;
use Laminas\Crypt\Password\Bcrypt;
use Redseanet\Resource\Model\Resource;
use Redseanet\Lib\Db\YsInsert;
use Redseanet\Api\Model\Rpc\{
    User
};
use Redseanet\Lib\Session\Segment;

class Customer extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Lib\Traits\Url;

    /**
     * @param int $id
     * @param string $token
     * @param string $username
     * @param string $password
     * @param string $uuid
     * @return array
     */
    public function customerValid($id, $token, $username, $password, $uuid = null)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $customer = new Model();
        $user = new User();
        $user->load(intval($id));
        //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($user)));
        //Bootstrap::getContainer()->get("log")->logException(new \Exception($this->decryptData($password,$user)));
        $customerId = $customer->valid($username, $this->decryptData($password, $user)) ? $customer->getId() : 0;
        if ($customerId) {
            $customer->load($customerId);
            $segment = new Segment('customer');
            $segment->set('customer', $customer->toArray());
            $resultData = ['id' => $customer->getId()];
            $attributes = new Attribute();
            $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                    ->where(['eav_entity_type.code' => Model::ENTITY_TYPE])
            ->where->notEqualTo('input', 'password');
            //echo $attributes->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
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
            $this->getContainer()->get('eventDispatcher')->trigger('customer.login.after', ['model' => $customer]);
            $resultData['avatar'] = $avatar;
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'login successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'login failure, useranme and password invaid'];
            return $this->responseData;
        }
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @return array
     */
    public function getcustomerInfo($id, $token, $customerId)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($customerId == '') {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'customer id can not be null'];
            return $this->responseData;
        }
        $customer = new Model();
        $customer->load($customerId);
        $resultData = ['id' => $customer->getId()];
        $attributes = new Attribute();

        $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE])->where->notEqualTo('input', 'password');
        //echo $attributes->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $attributes->load(true, true);
        $attributes->walk(function ($attribute) use (&$resultData, $customer) {
            $resultData[$attribute['code']] = $customer->offsetGet($attribute['code']);
        });
        $resultData['increment_id'] = $customer->offsetGet('increment_id');
        if (isset($resultData['avatar']) && $resultData['avatar'] != '') {
            $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $resultData['avatar']);
            unset($resultData['avatar']);
        } else {
            $avatar = $this->getPubUrl('frontend/images/placeholder.png');
        }
        $resultData['avatar'] = $avatar;
        unset($resultData['password']);
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get the user successfully'];
        return $this->responseData;
    }

    /**
     * @param string $sessionId
     * @param object $data
     * @return array
     * @throws SoapFault
     */
    public function customerCreate($id, $token, $data)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }

        $data = (array) $data;
        $config = $this->getContainer()->get('config');
        $attributes = new Attribute();
        $attributes->withSet()->where(['attribute_set_id' => $config['customer/registion/set']])
                ->where('(is_required=1 OR is_unique=1)')
                ->columns(['code', 'is_unique', 'type_id'])
                ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id', [], 'right')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);

        $unique = [];
        foreach ($attributes as $attribute) {
            if ($attribute['is_unique']) {
                $unique[] = $attribute['code'];
            }
        }
        if (count($unique) > 0) {
            $collection = new Collection();
            $collection->columns($unique);
            foreach ($unique as $code) {
                if (isset($data[$code])) {
                    $collection->where([$code => $data[$code]], 'OR');
                } else {
                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The field ' . $code . ' can not bet null'];
                    return $this->responseData;
                }
            }
            if (count($collection)) {
                foreach ($collection as $item) {
                    foreach ($unique as $code) {
                        if (isset($item[$code]) && $item[$code]) {
                            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The field ' . $code . ' has been used.'];
                            return $this->responseData;
                        }
                    }
                    break;
                }
            }
        }

        if (!empty($data['avatar'])) {
            $avatar = $data['avatar'];
            unset($data['avatar']);
            $name = 'avatar' . date('YmdHis') . mt_rand(10, 1000) . '.' . substr($avatar, 11, strpos($avatar, ';') - 11);
            $data['avatar'] = $name;
        }

        $user = new User();
        $user->load(intval($id));
        $data['password'] = $this->decryptData($data['password'], $user);
        $customer = new Model();
        $customer->setData([
            'id' => null,
            'attribute_set_id' => $config['customer/registion/set'],
            'group_id' => $config['customer/registion/group'],
            'type_id' => $attributes[0]['type_id'],
            'store_id' => 1,
            'language_id' => 1,
            'status' => 1
        ] + $data);
        $customer->save();
        if (!empty($avatar) && !empty($name)) {
            if (!is_dir(BP . 'pub/upload/customer/')) {
                mkdir(BP . 'pub/upload/customer/', 0777, true);
            }
            if (!is_dir(BP . 'pub/upload/customer/' . $customer->getId())) {
                mkdir(BP . 'pub/upload/customer/' . $customer->getId(), 0777, true);
            }
            $fp = fopen(BP . 'pub/upload/customer/' . $customer->getId() . '/' . $name, 'wb');
            fwrite($fp, base64_decode(trim(substr($avatar, strpos($avatar, ',') + 1))));
            fclose($fp);
        }
        $resultData = ['id' => $customer->getId()];
        $attributes = new Attribute();
        $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE])
        ->where->notEqualTo('input', 'password');

        $attributes->load(true, true);
        $attributes->walk(function ($attribute) use (&$resultData, $customer) {
            $resultData[$attribute['code']] = $customer->offsetGet($attribute['code']);
        });
        $resultData['increment_id'] = $customer->offsetGet('increment_id');
        if (isset($resultData['avatar']) && $resultData['avatar'] != '') {
            $avatar = $this->getBaseUrl('pub/upload/customer/' . $customer->getId() . '/' . $resultData['avatar']);
            unset($resultData['avatar']);
        } else {
            $avatar = $this->getPubUrl('frontend/images/placeholder.png');
        }
        unset($resultData['password']);
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'create the user successfully'];
        return $this->responseData;
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param araray $data
     * @return araray
     */
    public function customerUpdate($id, $token, $customerId, $data)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $customer = new Model();
        $customer->load($customerId);
        if ($customer->getId()) {
            $data = (array) $data;
            $config = $this->getContainer()->get('config');
            $attributes = new Attribute();
            $attributes->withSet()->where(['attribute_set_id' => $config['customer/registion/set']])
                    ->where(['is_unique' => 1])
                    ->columns(['code', 'is_unique', 'type_id'])
                    ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id', [], 'right')
                    ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
            $unique = [];
            foreach ($attributes as $attribute) {
                if ($attribute['is_unique']) {
                    $unique[] = $attribute['code'];
                }
            }
            if (count($unique) > 0) {
                $collection = new Collection();
                $collection->columns($unique);
                foreach ($unique as $code) {
                    if (isset($data[$code]) && $data[$code] != '') {
                        $collection->where([$code => $data[$code]], 'OR');
                    }
                }
                if (count($collection)) {
                    foreach ($collection as $item) {
                        foreach ($unique as $code) {
                            if (isset($data[$code]) && $data[$code] != '') {
                                if (isset($item[$code]) && $item[$code]) {
                                    echo $item[$code];
                                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The field ' . $code . ' has been used.'];
                                    return $this->responseData;
                                }
                            }
                        }
                        break;
                    }
                }
            }

            unset($data['password']);

            if (!empty($data['avatar'])) {
                $avatar = $data['avatar'];
                unset($data['avatar']);
                $name = 'avatar' . date('YmdHis') . mt_rand(10, 1000) . '.' . substr($avatar, 11, strpos($avatar, ';') - 11);
                if (!is_dir(BP . 'pub/upload/customer/')) {
                    mkdir(BP . 'pub/upload/customer/', 0777, true);
                }
                if (!is_dir(BP . 'pub/upload/customer/' . $customerId)) {
                    mkdir(BP . 'pub/upload/customer/' . $customerId, 0777, true);
                }
                $fp = fopen(BP . 'pub/upload/customer/' . $customerId . '/' . $name, 'wb');
                fwrite($fp, base64_decode(trim(substr($avatar, strpos($avatar, ',') + 1))));
                fclose($fp);
                $data['avatar'] = $name;
            }
            foreach (new Language() as $language) {
                $customer = new Model($language['id']);
                $customer->load($customerId);
                $customer->setData($data);
                $customer->save();
            }
            $resultData = $customer->load($customerId)->toArray();
            if (isset($resultData['avatar']) && $resultData['avatar'] != '') {
                $avatar = $this->getBaseUrl('pub/upload/customer/' . $customerId . '/' . $resultData['avatar']);
                unset($resultData['avatar']);
            } else {
                $avatar = $this->getPubUrl('frontend/images/placeholder.png');
            }
            $resultData['avatar'] = $avatar;
            unset($resultData['password']);
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'update the user infomation successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'do not find the customer, customer id :' . $code];
            return $this->responseData;
        }
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param string $oldCel
     * @param string $oldZone
     * @param string $zone
     * @return int
     */
    public function customerSaveCel($id, $token, $customerId, $oldCel, $oldZone, $cel, $zone)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $customer = new Model();
        $customer->load($cel, 'cel');
        if ($customer->getId()) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The field cel has been used'];
            return $this->responseData;
        }
        $customer = new Model();
        $customer->load($customerId);
        if ($customer->getId()) {
            $data = [
                'cel' => $cel,
                'zone' => $zone
            ];
            foreach (new Language() as $language) {
                $customer = new Model($language['id']);
                $customer->load($customerId);
                $this->getContainer()->get('log')->logException(new \Exception(json_encode($data)));
                $customer->setData($data)->save();
            }
            $resultData = $customer->toArray();
            unset($resultData['password']);
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'update the user phone successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The user do not exit'];
            return $this->responseData;
        }
    }

    /**
     * @param string $id int
     * @param string $token string
     * @param $cutomerId int
     * @param $data array
     * @return array
     */
    public function customerUpdatePassword($id, $token, $cutomerId, $data)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $customer = new Model();
        $user = new User();
        $user->load(intval($id));
        if (empty($data['password'])) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The field password is required and cannot be empty'];
            return $this->responseData;
        } elseif (strlen($data['password']) < 6) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Length of Password more than 6 Letters'];
            return $this->responseData;
        } else {
            $password = $this->decryptData($data['password'], $user);
        }
        if (!empty($cutomerId)) {
            $customer->load($cutomerId);
            if (empty($data['crpassword']) || !$customer->valid($customer['username'], ($this->decryptData($data['crpassword'], $user)))) {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The current password is incorrect'];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'do not find the customer'];
            return $this->responseData;
        }
        foreach (new Language() as $language) {
            $customer = new Model($language['id']);
            $customer->load($cutomerId);
            //unset($data['id']);
            //$this->getContainer()->get('log')->logException(new \Exception(json_encode($data)));
            $customer->setData(['password' => $password])->save();
        }
        $resultData = $customer->toArray();
        unset($resultData['password']);
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'update password successfully'];
        return $this->responseData;
    }
}
