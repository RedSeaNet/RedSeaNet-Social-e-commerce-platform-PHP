<?php

namespace Redseanet\Api\Model\Api;

use Exception;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use Redseanet\Api\Model\Soap\Session;
use Redseanet\Api\Model\Soap\User;
use SoapFault;
use Laminas\Crypt\PublicKey\Rsa;
use Laminas\Crypt\PublicKey\RsaOptions;
use Laminas\Crypt\PublicKey\Rsa\PrivateKey;
use Redseanet\Api\Model\Rpc\Role as RpcRole;
use Redseanet\Api\Model\Rpc\User as RpcUser;

class AbstractHandler implements HandlerInterface
{
    use \Redseanet\Lib\Traits\Container;

    protected $user = [];
    protected $session;
    public $responseData = ['statusCode' => '', 'data' => [], 'message' => ''];

    /**
     * @param string $sessionId
     * @param string $res
     * @return boolean
     * @throws SoapFault
     */
    protected function validateSessionId($sessionId, $res)
    {
        if ($sessionId) {
            $session = new Session();
            $session->load($sessionId);
            if ($session->getId()) {
                if (strtotime($session->offsetGet('log_date')) < time() - 3600) {
                    $session->remove();
                } else {
                    $this->session = $session;
                    $this->user = new User();
                    $this->user->load($session->offsetGet('user_id'));
                    if (!$this->user->getRole()->hasPermission($res)) {
                        throw new SoapFault('Client', 'Cannot access resource: ' . $res);
                    }
                    return true;
                }
            }
        }
        throw new SoapFault('Client', 'Unknown session id: ' . $sessionId);
    }

    /**
     * @param string $data
     * @param User $user
     * @return string
     */
    protected function encryptData($data, $user = null)
    {
        if (is_null($user)) {
            throw new SoapFault('Client', 'Unknown api user');
        }
        if (!empty($user['private_key'])) {
            $rsa = Rsa::factory([
                'public_key' => $user['public_key'],
                'private_key' => $user['private_key'],
                'pass_phrase' => $user['phrase'],
                'binary_output' => false,
            ]);
            return $rsa->encrypt($data);
        }
        return $data;
    }

    /**
     * @param string $data
     * @param User $user
     * @return string
     */
    protected function decryptData($data, $user)
    {
        if (!empty($user['private_key'])) {
            try {
                $rsa = Rsa::factory([
                    'public_key' => $user['public_key'],
                    'private_key' => $user['private_key'],
                    'pass_phrase' => $user['phrase'],
                    'binary_output' => false,
                    'openssl_padding' => OPENSSL_PKCS1_PADDING
                ]);
                //                $this->getContainer()->get("log")->logException(new \Exception(json_encode($user)));
                //                $this->getContainer()->get("log")->logException(new \Exception(json_encode($data)));
                //                $this->getContainer()->get("log")->logException(new \Exception(json_encode($rsa->decrypt($data))));
                return $rsa->decrypt($data);
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException(new \Exception($e));
            }
        }
        return $data;
    }

    /**
     * @param array $data
     * @param string|object $className
     * @return object
     */
    protected function response($data, $className = null)
    {
        $reflection = is_string($className) ? (new ReflectionClass($className)) :
                (is_null($className) ? (new ReflectionObject($this)) : (new ReflectionObject($className)));
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        if (empty($properties)) {
            $result = $data;
        } else {
            $result = [];
            foreach ($properties as $property) {
                $result[$property->getName()] = $data[$property->getName()] ?? null;
            }
        }
        return (object) $result;
    }

    /**
     * @param array $data
     * @return json string
     */
    public function arrayToJson($data)
    {
        return json_encode($data);
    }

    /**
     * @param jsonsting $jsonSting
     * @return json string
     */
    public function jsonToarray($jsonSting)
    {
        return json_decode($jsonSting, true);
    }

    /**
     * @param sting $Sting
     * @return string
     */
    public function base64Encrypt($Sting)
    {
        return base64_encode($Sting);
    }

    /**
     * @param sting $Sting
     * @return string
     */
    public function base64Dencrypt($Sting)
    {
        return base64_decode($Sting);
    }

    /**
     * @param string $token
     * @param string $res
     * @return array
     */
    public function validateToken($id, $token, $res, $checkPermission = true)
    {
        $this->responseData = ['statusCode' => '', 'data' => [], 'message' => 'token verify successfully'];
        if ($token) {
            $token = base64_decode($token);
            $user = new RpcUser();
            $user->load(intval($id));
            $tokenData = $this->jsonToarray($this->decryptData($token, $user));
            if (!isset($tokenData['uuid']) || $tokenData['uuid'] == '') {
                $this->responseData = ['statusCode' => '401', 'data' => [], 'message' => 'token is invaild 1'];
                return $this->responseData;
            }
            if (!isset($tokenData['apiUserId']) || $tokenData['apiUserId'] == '') {
                $this->responseData = ['statusCode' => '401', 'data' => [], 'message' => 'token is invaild 2'];
                return $this->responseData;
            }
            if (!isset($tokenData['ApiUserName']) || $tokenData['ApiUserName'] == '') {
                $this->responseData = ['statusCode' => '401', 'data' => [], 'message' => 'token is invaild 3'];
                return $this->responseData;
            }
            if (!isset($tokenData['loginTime']) || $tokenData['loginTime'] == '') {
                $this->responseData = ['statusCode' => '401', 'data' => [], 'message' => 'token is invaild 4'];
                return $this->responseData;
            }
            if (time() > ($tokenData['loginTime'] + 86400)) {
                $this->responseData = ['statusCode' => '401', 'data' => [], 'message' => 'token is expired 5'];
                return $this->responseData;
            }
            if ($res == 'apiValidateToken') {
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'token verify successfully'];
                return $this->responseData;
            } else {
                if ($checkPermission) {
                    if (!$user->getRole()->hasPermission($res)) {
                        $this->responseData = ['statusCode' => '401', 'data' => [], 'message' => 'Cannot access resource: ' . $res];
                        return $this->responseData;
                    }
                }
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'token verify successfully'];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '401', 'data' => [], 'message' => 'token can not be null'];
            return $this->responseData;
        }
        return $this->responseData;
    }
}
