<?php

namespace Redseanet\Message\Model\Client;

interface ClientInterface
{
    /**
     * @param string|array $phone
     * @param string $code
     * @param array $params
     * @return bool
     */
    public function send($phone, $code, $params = null);

    /**
     * @param string $str
     * @return string
     */
    public function sign($str);
}
