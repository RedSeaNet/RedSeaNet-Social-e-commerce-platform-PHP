<?php

namespace Redseanet\Message\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;

class Message extends AbstractHandler
{
    private $sender;

    private function getSender($class)
    {
        if (is_null($this->sender)) {
            $this->sender = new $class();
        }
        return $this->sender;
    }

    /**
     * @param string $id
     * @param string $token
     * @param string $to
     * @param string $template
     * @param string $code
     * @return array
     */
    public function sendSmsCodeForCusotmer($id, $token, $to, $template, $code)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $params = [];
        $params['code'] = $code;
        $template = 'customer/message/' . $template;
        $config = $this->getContainer()->get('config');
        if ($service = $config['message/general/service']) {
            if (isset($config[$template]) && $config[$template] != '') {
                if (isset($to) && $to != '') {
                    $sendResult = $this->getSender($config['message/' . $service . '/model'])
                            ->send($to, $config[$template], $params);
                    if ($sendResult) {
                        $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'send sms code successfully'];
                        return $this->responseData;
                    } else {
                        $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'end sms message failure, plesae contact administrator'];
                        return $this->responseData;
                    }
                } else {
                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'phone can not be null'];
                    return $this->responseData;
                }
            } else {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'send sms message failure, template code incorrect'];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'send sms message failure, because sms configuration incorrect, please contact administor'];
            return $this->responseData;
        }
    }
}
