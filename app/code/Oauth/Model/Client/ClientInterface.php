<?php

namespace Redseanet\Oauth\Model\Client;

interface ClientInterface
{
    /**
     * @param string $state
     * @return string
     */
    public function redirect($state);

    /**
     * @param string $token
     * @return array $result
     */
    public function access($token);

    /**
     * @param string $token
     * @param string $openId
     * @return array $result
     */
    public function getInfo($token, $openId);
}
