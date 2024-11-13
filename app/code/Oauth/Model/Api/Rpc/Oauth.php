<?php

namespace Redseanet\Oauth\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Oauth\Model\Client;
use Redseanet\Oauth\Model\Collection\Client as Collection;
use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Customer as CustomerCollection;
use Redseanet\Resource\Model\Resource;
use Redseanet\Ministry\Model\Collection\Ministry as ministryCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Db\YsInsert;
use Redseanet\Lib\Session\Segment;

class Oauth extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Url;

    /**
     * @param int $id
     * @param string $token
     * @param string $serverName
     * @param string $openId
     * @param array $data
     * @return int
     */
    public function oauthLogin($id, $token, $serverName, $openId, $data = [])
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }

        $client = new Client();
        $client->load([
            'oauth_server' => $serverName,
            'open_id' => $openId
        ]);
        $customerId = $client->getId() ?: 0;
        if ($customerId) {
            $customer = new Customer();
            $customer->load($customerId);
            $this->getContainer()->get('eventDispatcher')->trigger('customer.login.after', ['model' => $customer]);

            $segment = new Segment('customer');
            $segment->set('customer', $customer);
            $resultData = ['id' => $customer->getId()];
            $attributes = new Attribute();
            $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                    ->where(['eav_entity_type.code' => Customer::ENTITY_TYPE])
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
                $avatar = $this->getPubUrl('frontend/images/placeholder.png');
            }
            $this->getContainer()->get('eventDispatcher')->trigger('customer.login.after', ['model' => $customer]);
            $resultData['avatar'] = $avatar;
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'login successfully'];
            return $this->responseData;
        } else {
            if (is_array($data) && count($data) > 0) {
                $config = $this->getContainer()->get('config');
                $attributes = new Attribute();
                $attributes->withSet()->where(['attribute_set_id' => $config['customer/registion/set']])
                        ->where('(is_required=1 OR is_unique=1)')
                        ->columns(['code', 'is_unique', 'type_id'])
                        ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id', [], 'right')
                        ->where(['eav_entity_type.code' => Customer::ENTITY_TYPE]);
                $unique = [];
                foreach ($attributes as $attribute) {
                    if ($attribute['is_unique']) {
                        $unique[] = $attribute['code'];
                    }
                }
                if (count($unique) > 0) {
                    $collection = new CustomerCollection();
                    $collection->columns($unique);
                    foreach ($unique as $code) {
                        if (isset($data[$code])) {
                            $collection->where([$code => $data[$code]], 'OR');
                        }
                    }
                    if (count($collection)) {
                        foreach ($collection as $item) {
                            foreach ($unique as $code) {
                                if (isset($item[$code]) && $item[$code]) {
                                    //throw new SoapFault('Client', 'The field ' . $code . ' has been used.');
                                    $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'The field ' . $code . ' has been used.'];
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
                }
                if (isset($avatar)) {
                    if (!is_dir(BP . 'pub/upload/customer/')) {
                        mkdir(BP . 'pub/upload/customer/', 0777, true);
                    }
                    if (!is_dir(BP . 'pub/upload/customer/avatar/')) {
                        mkdir(BP . 'pub/upload/customer/avatar/', 0777, true);
                    }
                    if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $avatar)) {
                        $name = $customerId . '.' . substr($avatar, 11, strpos($avatar, ';') - 11);
                        $fp = fopen(BP . 'pub/upload/customer/avatar/' . $name, 'wb');
                        fwrite($fp, base64_decode(trim(substr($avatar, strpos($avatar, ',') + 1))));
                        fclose($fp);
                    } else {
                        $name = $customerId . '.png';
                        $fp = fopen(BP . 'pub/upload/customer/avatar/' . $name, 'wb');
                        fwrite($fp, @file_get_contents($avatar));
                        fclose($fp);
                    }
                    $data['avatar'] = $name;
                }

                $customer = new Customer();
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

                $client = new Client();
                $client->setData([
                    'customer_id' => $customerId,
                    'oauth_server' => $serverName,
                    'open_id' => $openId
                ])->save();
            }

            $Customer = new Customer();
            $Customer->load($customerId);
            $this->getContainer()->get('eventDispatcher')->trigger('customer.login.after', ['model' => $Customer]);
        }
        $result = ['id' => $Customer->getId()];
        $attributes = new Attribute();
        $attributes->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Customer::ENTITY_TYPE])
        ->where->notEqualTo('input', 'password');
        //echo $attributes->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $attributes->load(true, true);
        $attributes->walk(function ($attribute) use (&$result, $Customer) {
            $result[$attribute['code']] = $Customer->offsetGet($attribute['code']);
        });
        $result['increment_id'] = $Customer->offsetGet('increment_id');
        $oauth = [];
        $collection = new Collection();
        $collection->columns(['oauth_server'])
                ->where(['customer_id' => $customerId]);

        $collection->walk(function ($item) use (&$oauth) {
            $oauth[] = $item['oauth_server'];
        });
        if (isset($result['avatar']) && $result['avatar'] != '') {
            $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $result['avatar']);
            unset($result['avatar']);
        } else {
            $avatar = $this->getPubUrl('frontend/images/placeholder.png');
        }
        $result['avatar'] = $avatar;
        $result['oauth'] = $oauth;
        $this->responseData = ['statusCode' => '200', 'data' => $result, 'message' => 'oauth login successfully'];
        return $this->responseData;
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param string $serverName
     * @param string $openId
     */
    public function oauthBind($id, $token, $customerId, $serverName, $openId)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $client = new Client();
        $client->setData([
            'customer_id' => $customerId,
            'oauth_server' => $serverName,
            'open_id' => $openId
        ])->save();
        $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'bind successfully'];
        return $this->responseData;
    }

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @return array
     */
    public function oauthBindedServer($id, $token, $customerId)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $collection = new Collection();
        $collection->columns(['oauth_server'])
                ->where(['customer_id' => $customerId]);
        $result = [];
        $collection->walk(function ($item) use (&$result) {
            $result[] = $item['oauth_server'];
        });
        $this->responseData = ['statusCode' => '200', 'data' => $result, 'message' => 'get oauth binded server list successfully'];
        return $this->responseData;
    }
}
