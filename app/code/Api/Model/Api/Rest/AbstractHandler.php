<?php

namespace Redseanet\Api\Model\Api\Rest;

use Redseanet\Api\Model\Collection\Rest\Attribute;

abstract class AbstractHandler
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\Filter;

    protected $request = null;
    protected $response = null;
    protected $authOptions = [];

    protected function getRequest()
    {
        if (is_null($this->request)) {
            $this->request = $this->getContainer()->get('request');
        }
        return $this->request;
    }

    protected function getResponse()
    {
        if (is_null($this->response)) {
            $this->response = $this->getContainer()->get('response');
        }
        return $this->response;
    }

    public function setAuthOptions(array $authOptions)
    {
        $this->authOptions = $authOptions;
        return $this;
    }

    protected function getAttributes($type, $isRead = true)
    {
        $attributes = new Attribute();
        $attributes->columns(['attributes'])
                ->where([
                    'operation' => $isRead ? 1 : 0,
                    'resource' => $type,
                    'role_id' => $this->authOptions['role_id']
                ]);
        return count($attributes) ? explode(',', $attributes[0]['attributes']) : [];
    }
}
