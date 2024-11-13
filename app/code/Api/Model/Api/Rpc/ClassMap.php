<?php

namespace Redseanet\Api\Model\Api\Rpc;

use Error;
use Exception;

class ClassMap
{
    use \Redseanet\Lib\Traits\Container;

    protected $responseData = ['statusCode' => '', 'data' => [], 'message' => ''];

    public function __call($name, $arguments)
    {
        $config = $this->getContainer()->get('config')['api']['rpc'] ?? [];
        if (isset($config[$name]) && is_subclass_of($config[$name], '\\Redseanet\\Api\\Model\\Api\\HandlerInterface')) {
            return call_user_func_array([new $config[$name](), $name], $arguments);
        } else {
            $this->responseData['statusCode'] = '404';
            $this->responseData['message'] = 'method is not right';
            return $this->responseData;
        }
    }
}
