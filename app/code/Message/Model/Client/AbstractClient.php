<?php

namespace Redseanet\Message\Model\Client;

abstract class AbstractClient implements ClientInterface
{
    use \Redseanet\Lib\Traits\Container;

    protected function request($url, $params = [], $method = 'GET', array $headers = [])
    {
        $client = curl_init();
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        curl_setopt($client, CURLOPT_URL, $url);
        if ($method === 'POST') {
            curl_setopt($client, CURLOPT_POST, 1);
            curl_setopt($client, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($client, CURLOPT_HEADER, 0);
        if (!empty($headers)) {
            curl_setopt($client, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($client);
        curl_close($client);
        return $response;
    }
}
