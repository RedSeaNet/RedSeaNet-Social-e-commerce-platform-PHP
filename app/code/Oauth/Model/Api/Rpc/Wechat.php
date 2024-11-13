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
use Redseanet\Api\Model\Rpc\{
    User
};

class Wechat extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Url;

    /**
     * @param int $id
     * @param string $token
     * @param string $code
     * @return array
     */
    public function wechatCodeToOpenId($id, $token, $code)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $res = $this->convertCodeToOpenId($code);
        $this->responseData = ['statusCode' => '200', 'data' => $res, 'message' => 'code to open id'];
        return $this->responseData;
    }

    /**
     * @param int $id
     * @param string $token
     * @param string $code
     * @return array
     */
    public function wechatMiniprogramLogin($id, $token, $code, $username = '', $password = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $res = $this->convertCodeToOpenId($code);
        if (isset($res['unionid']) && $res['unionid'] != '') {
            if (!empty($username) && !empty($password)) {
                $customer = new Customer();
                $user = new User();
                $user->load(intval($id));
                //                Bootstrap::getContainer()->get("log")->logException(new \Exception($password));
                //                Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($user)));
                //                Bootstrap::getContainer()->get("log")->logException(new \Exception($this->decryptData($password,$user)));
                //                Bootstrap::getContainer()->get("log")->logException(new \Exception($password));
                $customerId = $customer->valid($username, $this->decryptData($password, $user)) ? $customer->getId() : 0;
                if ($customerId) {
                    $customer->load($customerId);
                    $segment = new Segment('customer');
                    $segment->set('customer', $customer->toArray());
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
                        $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                    }
                    $this->getContainer()->get('eventDispatcher')->trigger('customer.login.after', ['model' => $customer]);
                    $resultData['avatar'] = $avatar;
                    $resultData['openid'] = $res['openid'];
                    $resultData['unionid'] = $res['unionid'];
                    $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'login successfully'];
                    return $this->responseData;
                } else {
                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'login failure, useranme and password invaid'];
                    return $this->responseData;
                }
            } else {
                $client = new Client();
                $client->load([
                    'oauth_server' => 'wechat',
                    'open_id' => $res['unionid']
                ]);
                $customerId = $client['customer_id'] ?: 0;
                if ($customerId != 0) {
                    $customer = new Customer();
                    $customer->load($customerId);
                    $segment = new Segment('customer');
                    $segment->set('customer', $customer->toArray());
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
                    $resultData['openid'] = $res['openid'];
                    $resultData['unionid'] = $res['unionid'];
                    $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'login successfully'];
                    return $this->responseData;
                } else {
                    $this->responseData = ['statusCode' => '200', 'data' => $res, 'message' => 'code to open id'];
                    return $this->responseData;
                }
            }
        } else {
            $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'get openid error'];
            return $this->responseData;
        }
        $this->responseData = ['statusCode' => '200', 'data' => $res, 'message' => 'code to open id'];
        return $this->responseData;
    }

    protected function convertCodeToOpenId($code)
    {
        $config = $this->getContainer()->get('config');
        $appid = $config['oauth/wechat/mini_appid'];
        $appsecret = $config['oauth/wechat/mini_secret'];
        $grant_type = 'authorization_code';
        $params = 'appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $code . '&grant_type=' . $grant_type;
        $url = 'https://api.weixin.qq.com/sns/jscode2session?' . $params;
        Bootstrap::getContainer()->get('log')->logException(new \Exception($url));
        $resJson = file_get_contents($url);
        Bootstrap::getContainer()->get('log')->logException(new \Exception($resJson));
        $res = json_decode($resJson, true);
        return $res;
    }

    protected function getUserInfo($access_token, $openid)
    {
        $userInfo_params = 'access_token=' . $access_token . '&openid=' . $openid;
        $userInfo_url = 'https://api.weixin.qq.com/sns/userinfo?' . $userInfo_params;
        $userInfo_curl = curl_init();
        curl_setopt($userInfo_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($userInfo_curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($userInfo_curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($userInfo_curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($userInfo_curl, CURLOPT_URL, $userInfo_url);
        $userInfo_resJson = curl_exec($userInfo_curl);
        curl_close($userInfo_curl);
        //echo $userInfo_resJson;
        Bootstrap::getContainer()->get('log')->logException(new \Exception($userInfo_resJson));
        $userInfo_res = json_decode($userInfo_resJson, true);
        return $userInfo_res;
    }
}
