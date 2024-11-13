<?php

namespace Redseanet\Api\Model\Api\Rpc;

use Redseanet\Api\Model\Rpc\User;

final class General extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    public function getToken($username, $password, $uuid = '')
    {
        $user = new User();
        $user->load($username, 'username');
        if ($user->getId() && $user->valid($username, $this->decryptData($password, $user))) {
            $loginTime = time();
            $_tokenData = ['uuid' => $uuid, 'loginTime' => $loginTime, 'apiUserId' => $user->getId(), 'ApiUserName' => $username];
            $token = $this->encryptData($this->arrayToJson($_tokenData), $user);
            $this->responseData['data'] = [
                'token' => base64_encode($token),
                'id' => $user->getId(),
                'time' => $loginTime
            ];
            $this->responseData['statusCode'] = '200';
            $this->responseData['message'] = 'create token successfully';
            return $this->responseData;
        }
        $this->responseData['statusCode'] = '401';
        $this->responseData['message'] = 'you is not a verified user';
        return $this->responseData;
    }

    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    public function apiValidateToken($id, $token, $res)
    {
        $result = $this->validateToken($id, $token, $res);
        //$result=$this->arrayToJson([$id, $token, $res]);
        return $result;
    }

    /**
     * @param string $key
     * @param string $prefix
     */
    public function flushCache($key, $prefix)
    {
        $this->getContainer()->get('cache')->delete($key, $prefix, false);
    }
}
