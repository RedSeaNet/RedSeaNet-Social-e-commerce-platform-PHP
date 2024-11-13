<?php

namespace Redseanet\Customer\Model\Api\Soap;

use Redseanet\Api\Model\Api\AbstractHandler;
use Redseanet\Customer\Exception\InvalidSmsCodeException;
use Redseanet\Customer\Model\Collection\Customer as Collection;
use Redseanet\Customer\Model\Customer as Model;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Oauth\Model\Api\Soap\Oauth;
use SoapFault;
use Laminas\Db\Sql\Where;
use Redseanet\Lib\Bootstrap;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;
use Laminas\Crypt\Password\Bcrypt;
use Redseanet\Resource\Model\Resource;
use Redseanet\Ministry\Model\Collection\Ministry as ministryCollection;
use Redseanet\Lib\Db\YsInsert;

class Customer extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    use \Redseanet\Email\Traits\SendGrid;

    /**
     * @param string $sessionId
     * @param string $username
     * @param string $password
     * @param string $uuid
     * @return int
     */
    public function customerValid($sessionId, $username, $password, $uuid = null, $areaCode = null, $returnInfo = false)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $customerCollection = new customerCollection();
        $customerCollection->where(['cel' => $username, 'zone' => $areaCode]);

        $customerAreaCode = $customerCollection->load(true, true);
        $tmpCustomeV = [];
        for ($i = 0; $i < count($customerAreaCode); $i++) {
            if ($customerAreaCode[$i]['status'] && (new Bcrypt())->verify($password, $customerAreaCode[$i]['password'])) {
                $tmpCustomeV = $customerAreaCode[$i];
            }
        }
        if (count($tmpCustomeV) > 0) {
            $customerId = $tmpCustomeV['id'];
            //Bootstrap::getContainer()->get("log")->logException(new \Exception($customerCollection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform())));
        } else {
            $customer = new Model();
            $customerId = $customer->valid($username, $this->decryptData($password)) ? $customer->getId() : 0;
        }
        if ($customerId) {
            $this->getContainer()->get('eventDispatcher')->trigger('livechat.send', [
                'customerId' => $customerId,
                'type' => 'uuid_check',
                'message' => $uuid
            ]);
            $this->getContainer()->get('eventDispatcher')->trigger('customer.login.after', ['model' => $customer]);
        }
        if ($returnInfo && $customerId) {
            $customer = new Model();
            $customer->load($customerId);
            $result = ['id' => $customer->getId()];
            $attributes = new Attribute();
            $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                    ->where(['eav_entity_type.code' => Model::ENTITY_TYPE])
            ->where->notEqualTo('input', 'password');
            //echo $attributes->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
            $attributes->load(true, true);
            $attributes->walk(function ($attribute) use (&$result, $customer) {
                $result[$attribute['code']] = $customer->offsetGet($attribute['code']);
            });
            $result['increment_id'] = $customer->offsetGet('increment_id');
            $oauth = (new Oauth())->oauthBindedServer($sessionId, $customerId);
            $result['oauth'] = $oauth;
            return $this->response($result);
        }
        return $customerId;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function customerInfo($sessionId, $customerId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $customer = new Model();
        $customer->load($customerId);
        $result = ['id' => $customer->getId()];
        $attributes = new Attribute();
        $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE])
        ->where->notEqualTo('input', 'password');
        //echo $attributes->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $attributes->load(true, true);
        $attributes->walk(function ($attribute) use (&$result, $customer) {
            $result[$attribute['code']] = $customer->offsetGet($attribute['code']);
        });
        $result['increment_id'] = $customer->offsetGet('increment_id');

        try {
            $oauth = (new Oauth())->oauthBindedServer($sessionId, $customerId);
            $result['oauth'] = $oauth;
        } catch (SoapFault $e) {
        }
        return $this->response($result);
    }

    /**
     * @param string $sessionId
     * @param object $data
     * @return array
     * @throws SoapFault
     */
    public function customerCreate($sessionId, $data, $returnInfo = false)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
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
                }
            }

            //        echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
            if (count($collection)) {
                foreach ($collection as $item) {
                    foreach ($unique as $code) {
                        if (isset($item[$code]) && $item[$code]) {
                            throw new SoapFault('Client', 'The field ' . $code . ' has been used.');
                        }
                    }
                    break;
                }
            }
        }
        if (!empty($data['avatar'])) {
            $avatar = $data['avatar'];
            unset($data['avatar']);
        }
        if (!empty($avatar)) {
            $name = date('YmdHis') . mt_rand(10, 1000) . '.' . substr($avatar, 11, strpos($avatar, ';') - 11);
            if (isset($config['resource/main/enable']) && $config['resource/main/enable'] == 'awss3') {
                $resourceModel = new Resource();
                $configS3 = $resourceModel->getAwsConfig();
                $configS3['path'] = BP . 'pub/upload/customer/' . $name;
                $configS3['filename'] = $name;
                $configS3['bucketPath'] = 'upload/customer/';
                $configS3['body'] = base64_decode(trim(substr($avatar, strpos($avatar, ',') + 1)));
                $configS3['ContentType'] = 'image/' . substr($avatar, 11, strpos($avatar, ';') - 11);
                $s3R = $resourceModel->s3MoveFileBase64($configS3);
                $data['avatar'] = $name;
            } else {
                if (!is_dir(BP . 'pub/upload/customer/')) {
                    mkdir(BP . 'pub/upload/customer/', 0777, true);
                }
                $fp = fopen(BP . 'pub/upload/customer/' . $name, 'wb');
                fwrite($fp, base64_decode(trim(substr($avatar, strpos($avatar, ',') + 1))));
                fclose($fp);
                $data['avatar'] = $name;
            }
        }
        if (!empty($data['defaultAvatar']) && $data['avatar'] == '') {
            $data['avatar'] = $data['defaultAvatar'];
        }
        $data['password'] = $this->decryptData($data['password']);
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

        $customerId = $customer->getId();
        $ministryCollection = new ministryCollection();
        $ministryCollection->where(['autofllow' => '1']);
        $ministryCollection->load();
        $_tmpMinitrySubscription = [];
        if (count($ministryCollection) > 0) {
            //$_tmpMinitry=[];
            foreach ($ministryCollection as $ministryK => $ministryV) {
                //$insert->addRows(array($ministryV['id'], $customerId));
                $_tmpMinitrySubscription[] = $ministryV['id'];
            }
            //$insert->execute();
        }
        if (isset($data['subscription']) && $data['subscription'] != '') {
            $_tmpM = explode(',', $data['subscription']);
            $_tmpMinitrySubscription = array_unique(array_merge($_tmpMinitrySubscription, $_tmpM));
            //var_dump($subscribe);
        }
        if (count($_tmpMinitrySubscription) > 0) {
            $insert = new YsInsert();
            $insert->into('ministry_follow')->columns(['ministry_id', 'customer_id']);
            foreach ($_tmpMinitrySubscription as $k => $v) {
                $insert->addRows([$v, $customerId]);
            }
            $insert->execute();
        }
        //echo $ministryCollection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform()); exit('tst----');
        //        $this->getTableGateway('ministry_follow')
        //                ->insert(['ministry_id' => 21, 'customer_id' => $customer->getId()]);
        //        if (!empty($data['subscribe'])) {
        //            $this->getContainer()->get('eventDispatcher')->trigger('subscribe', ['data' => $data]);
        //        }
        if ($returnInfo) {
            $customer = new Model();
            $customer->load($customerId);
            $result = ['id' => $customer->getId()];
            $attributes = new Attribute();
            $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                    ->where(['eav_entity_type.code' => Model::ENTITY_TYPE])
            ->where->notEqualTo('input', 'password');
            //echo $attributes->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
            $attributes->load(true, true);
            $attributes->walk(function ($attribute) use (&$result, $customer) {
                $result[$attribute['code']] = $customer->offsetGet($attribute['code']);
            });
            $result['increment_id'] = $customer->offsetGet('increment_id');
            $oauth = (new Oauth())->oauthBindedServer($sessionId, $customerId);
            $result['oauth'] = $oauth;
            return $this->response($result);
        }

        return $customer->getId();
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param object $data
     * @return int
     * @throws SoapFault
     */
    public function customerSave($sessionId, $customerId, $data)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
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
        $collection = new Collection();
        $flag = false;
        $collection->columns($unique);
        $where = new Where();
        $flag = false;
        foreach ($unique as $code) {
            if (isset($data[$code])) {
                $flag = true;
                $predicate = new Where();
                $predicate->equalTo($code, $data[$code]);
                $where->orPredicate($predicate);
                $flag = true;
            }
        }
        $collection->getSelect()->where->notEqualTo('id', $customerId)->andPredicate($where);
        if ($flag && count($collection)) {
            foreach ($collection as $item) {
                foreach ($unique as $code) {
                    if (isset($item[$code]) && $item[$code]) {
                        throw new SoapFault('Client', 'The field ' . $code . ' has been used.');
                    }
                }
                break;
            }
        }
        if (!empty($data['password'])) {
            $data['password'] = $this->decryptData($data['password']);
        } else {
            unset($data['password']);
        }
        $customer = new Model();
        $customer->load($customerId);
        if (!empty($data['avatar'])) {
            $_tmpAvatarV = 1;
            if (strpos($customer->offsetGet('avatar'), '?v=')) {
                $_tmpAvatarV = intval(substr($customer->offsetGet('avatar'), (strpos($customer->offsetGet('avatar'), '?v=') + 3))) + 1;
            }
            $name = $customerId . '.' . substr($data['avatar'], 11, strpos($data['avatar'], ';') - 11);
            if (isset($config['resource/main/enable']) && $config['resource/main/enable'] == 'awss3') {
                $resourceModel = new Resource();
                $configS3 = $resourceModel->getAwsConfig();
                $configS3['path'] = BP . 'pub/upload/customer/' . $name;
                $configS3['filename'] = $name;
                $configS3['bucketPath'] = 'upload/customer/';
                $configS3['body'] = base64_decode(trim(substr($data['avatar'], strpos($data['avatar'], ',') + 1)));
                $configS3['ContentType'] = 'image/' . substr($data['avatar'], 11, strpos($data['avatar'], ';') - 11);
                $s3R = $resourceModel->s3MoveFileBase64($configS3);
                Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($s3R)));
                $data['avatar'] = $name . '?v=' . $_tmpAvatarV;
            } else {
                if (!is_dir(BP . 'pub/upload/customer/')) {
                    mkdir(BP . 'pub/upload/customer/', 0777, true);
                }
                $fp = fopen(BP . 'pub/upload/customer/' . $name, 'wb');
                fwrite($fp, base64_decode(trim(substr($data['avatar'], strpos($data['avatar'], ',') + 1))));
                fclose($fp);
                $data['avatar'] = $name;
            }
        }

        if ($customer->getId()) {
            unset($data['id']);
            //            $customer->setData($data);
            //            $this->getContainer()->get('log')->logException(new \Exception(json_encode($data)));
            //            $customer->save();
            foreach (new Language() as $language) {
                $customer = new Model($language['id']);
                $customer->load($customerId);
                if ($customer->getId()) {
                    unset($data['id']);
                    $customer->setData($data);
                    $customer->save();
                    //                if (!empty($data['subscribe'])) {
                    //                    $this->getContainer()->get('eventDispatcher')->trigger('subscribe', ['data' => $data]);
                    //                }
                    //$result = $customer->getId();
                }
            }
            if (!empty($data['subscribe'])) {
                $this->getContainer()->get('eventDispatcher')->trigger('subscribe', ['data' => $data]);
            }
        }
        return $customerId;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param string $oldCel
     * @param string $oldZone
     * @param string $oldCode
     * @param string $cel
     * @param string $zone
     * @param string $code
     * @param bool $isIos
     * @return int
     */
    public function customerSaveCel($sessionId, $customerId, $oldCel, $oldZone, $oldCode, $cel, $zone, $code, $isIos)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $customer = new Model();
        $customer->load($cel, 'cel');
        if ($customer->getId()) {
            return new SoapFault('Client', 'The field cel has been used.');
        }
        $customer = new Model();
        $customer->load($customerId);
        if ($customer->getId()) {
            try {
                $this->getContainer()->get('eventDispatcher')->trigger('sms.send', ['model' => [
                    'isIos' => $isIos,
                    'verification' => $oldCode,
                    'cel' => $oldCel,
                    'zone' => $oldZone
                ]]);
            } catch (InvalidSmsCodeException $e) {
                return new InvalidSmsCodeException('Client', 'Invalid verification code for old phone.');
            }
            try {
                $data = [
                    'isIos' => $isIos,
                    'verification' => $code,
                    'cel' => $cel,
                    'zone' => $zone
                ];
                foreach (new Language() as $language) {
                    $customer = new Model($language['id']);
                    $customer->load($customerId);
                    $this->getContainer()->get('log')->logException(new \Exception(json_encode($data)));
                    $customer->setData($data)->save();
                    unset($data['verification']);
                }
            } catch (InvalidSmsCodeException $e) {
                return new InvalidSmsCodeException('Client', 'Invalid verification code for new phone.');
            }
            return $customer->getId();
        }
        return 0;
    }

    /**
     * @param string $sessionId
     * @param object $data
     * @return bool
     */
    public function customerSavePassword($sessionId, $data)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $customer = new Model();
        if (empty($data['password'])) {
            return new SoapFault('Client', 'The field password is required and cannot be empty.');
        } elseif (strlen($data['password']) < 6) {
            return new SoapFault('Client', 'Length of Password more than 6 Letters');
        } else {
            $password = $this->decryptData($data['password']);
        }
        if (!empty($data['id'])) {
            $customer->load($data['id']);
            //            if (empty($data['cpassword']) || $data['password'] != ($data['cpassword'] = $this->decryptData($data['cpassword']))) {
            //                return new SoapFault('Client', 'The confirm password is not equal to the password.');
            //            } else
            if (empty($data['crpassword']) || !$customer->valid($customer['cel'], ($this->decryptData($data['crpassword'])))) {
                return new SoapFault('Client', 'The current password is incorrect.');
            }
        } elseif (isset($data['cel']) && !empty($data['cel'])) {
            $customer->load($data['cel'], 'cel');
        } elseif (isset($data['email']) && !empty($data['email'])) {
            $customer->load($data['email'], 'email');
        } else {
            return new SoapFault('Client', 'Bad request');
        }

        if ($id = $customer->getId()) {
            foreach (new Language() as $language) {
                $customer = new Model($language['id']);
                $customer->load($id);
                //unset($data['id']);
                //$this->getContainer()->get('log')->logException(new \Exception(json_encode($data)));
                $customer->setData(['password' => $password])->save();
                //unset($data['verification']);
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param string $keywords
     * @param int $lastId
     * @param int $limit
     * @return array
     */
    public function customerSearch($sessionId, $customerId, $keywords, $lastId, $limit)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $config = $this->getContainer()->get('config');
        $attributes = new Attribute();
        $attributes->withSet()->where(['attribute_set_id' => $config['customer/registion/set']])
                ->where(['searchable' => 1])
                ->columns(['code', 'type'])
                ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id', [], 'right')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE])
        ->where->notEqualTo('input', 'password');
        $attributes->load(true, true);
        $collection = new Collection();
        $select = $collection->getSelect();
        $select->limit($limit);
        $where = $select->where;
        $where->notEqualTo('id', $customerId);
        if ($lastId) {
            $where->greaterThan('id', $lastId);
        }
        $constraint = '(';
        foreach ($attributes as $attribute) {
            if (in_array($attribute['type'], ['varchar', 'text', 'datetime'])) {
                $constraint .= $attribute['code'] . '=\'' . $keywords . '\' OR ';
            } else {
                $constraint .= $attribute['code'] . '=' . $keywords . ' OR ';
            }
        }
        $select->where(preg_replace('/ OR $/', ')', $constraint));
        $collection->load(true, true);
        $result = [];
        foreach ($collection as $item) {
            $result[] = $this->response($item, '\\Redseanet\\Social\\Model\\Api\\Soap\\Friend');
        }
        return $result;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param array $phones
     * @param int $lastId
     * @param int $limit
     * @return array
     */
    public function customerSearchPhones($sessionId, $customerId, $phones, $lastId, $limit)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $config = $this->getContainer()->get('config');

        $collection = new Collection();
        $select = $collection->getSelect();

        $select->limit(intval($limit));
        $where = $select->where;
        if ($customerId != '') {
            $where->notEqualTo('id', $customerId);
        }
        if ($lastId) {
            $where->greaterThan('id', $lastId);
        }
        $select->where(['cel' => $phones]);
        //Bootstrap::getContainer()->get("log")->logException(new \Exception($collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform())));

        $collection->load(true, true);
        $result = [];
        //Bootstrap::getContainer()->get("log")->logException(new \Exception($collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform())));
        foreach ($collection as $item) {
            $result[] = (object) [
                'id' => $item['id'],
                'avatar' => $item['avatar'],
                'username' => $item['username'],
                'cel' => $item['cel']
            ]; //$this->response($item, '\\Redseanet\\Social\\Model\\Api\\Soap\\Friend');
        }
        return $result;
    }

    /**
     * @param string $sessionId
     * @param string $destinationNumber
     * @param string $message
     * @return array
     */
    public function pinpointSmsCode($sessionId, $destinationNumber = '', $message = '')
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $resourceModel = new Resource();

        $result = $resourceModel->sendSmsMessage($destinationNumber, $message);
        return $result;
    }

    /**
     * @param string $sessionId
     * @param string $emailAddress
     * @param string $subject
     * @param string $message
     * @return array
     */
    public function sendEmailSendGrid($sessionId, $emailAddress, $subject, $message)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $result = $this->sendEmail($emailAddress, $subject, $message);
        return $result;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param array $phones
     * @param int $lastId
     * @param int $limit
     * @return array
     */
    public function customerSearchEmail($sessionId, $email, $customerId = '', $lastId = '', $limit = '')
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $config = $this->getContainer()->get('config');
        $collection = new Collection();
        $select = $collection->getSelect();
        $where = $select->where;
        if ($customerId != '') {
            $where->notEqualTo('id', $customerId);
        }
        if ($limit != '') {
            $select->limit(intval($limit));
        }
        if ($lastId != '') {
            $where->greaterThan('id', $lastId);
        }
        $select->where(['email' => $email]);
        $collection->load(true, true);
        $result = [];
        foreach ($collection as $item) {
            $result[] = (object) [
                'id' => $item['id'],
                'avatar' => $item['avatar'],
                'username' => $item['username'],
                'cel' => $item['cel'],
                'email' => $item['email']
            ];
        }
        return $result;
    }

    /**
     * @param string $sessionId
     * @param array $files
     * @return array
     */
    public function getPresignedUrl($sessionId, $files = [])
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        if (count($files) > 0) {
            $resource = new Resource();
            $presignedUrl = $resource->getPresignedUrl($files);
            return $presignedUrl;
        } else {
            return [];
        }
    }
}
