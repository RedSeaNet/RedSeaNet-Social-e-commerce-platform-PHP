<?php

namespace Redseanet\Api\Controller;

use Exception;
use Redseanet\Api\Model\Api\Rpc\ClassMap;
use Redseanet\Lib\Controller\AbstractController;
use Redseanet\Lib\Bootstrap;

class RpcController extends AbstractController
{
    protected $type = 'json';
    protected $responseData = ['statusCode' => '200', 'data' => [], 'message' => ''];

    /**
     * {@inhertdoc}
     */
    public function dispatch($request = null, $routeMatch = null)
    {
        $response = $this->getResponse();
        if (!isset($_SERVER['HTTPS'])) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'SSL required'];
            return $this->response($this->responseData);
        }
        return parent::dispatch($request, $routeMatch);
    }

    protected function getRawPost()
    {
        $data = $this->getRequest()->getPost();
        if (is_object($data) && $this->type == 'xml') {
            $this->type = 'xml';
            $result = ['jsonrpc' => '2.0', 'id' => 1];
            $result['method'] = @$data->xpath('/methodCall/methodName')[0]->__toString();
            $result['params'] = [];
            foreach ($data->xpath('/methodCall/params/param/value') as $param) {
                $child = $param->children()[0];
                $result['params'][] = $child->getName() === 'base64' ? base64_decode($child->__toString()) : $child->__toString();
            }
            $data = $result;
        }
        return $data;
    }

    protected function prepareRequest()
    {
        $data = $this->getRawPost();
        if (!isset($data['method']) || empty($data['method']) || !is_string($data['method'])) {
            $this->responseData = ['statusCode' => '401', 'data' => [], 'message' => 'Invalid Request'];
        }
        return $data;
    }

    protected function response($result)
    {
        if ($this->type === 'xml') {
            if (isset($result['error'])) {
                $result = '<?xml version="1.0"?><methodResponse>
                    <fault><value><struct><member><name>faultCode</name>
                                   <value><int>' . $result['error']['code'] . '</int></value>
                    </member><member><name>faultString</name>
                                   <value><string>' . $result['error']['message'] . '</string></value>
                    </member></struct></value></fault></methodResponse>';
            } else {
                $type = gettype($result['result']);
                if ($type === 'integer') {
                    $type = 'int';
                } elseif ($type === 'boolean') {
                    $result['result'] = (int) $result['result'];
                }
                $result = '<?xml version="1.0"?><methodResponse>
                            <params><param><value><' . $type . '>' .
                        $result['result']
                        . '</' . $type . '></value></param></params></methodResponse>';
            }
        } elseif ($this->type === 'json') {
            return json_encode($result);
        }
        return $result;
    }

    public function indexAction()
    {
        $data = $this->prepareRequest();
        if ($this->responseData['statusCode'] != '200') {
            return $this->response($this->responseData);
        }
        // Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($data)));
        $classMap = new ClassMap();
        $result = call_user_func_array([$classMap, $data['method']], $data['params']);
        return $this->response($result);
    }
}
