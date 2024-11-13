<?php

namespace Redseanet\Oauth\Model\Client;

use Redseanet\Oauth\Model\Client;
use Redseanet\Oauth\Model\Collection\Client as clientCollection;

abstract class AbstractClient implements ClientInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\Url;

    protected function request($url, $params = [], $method = 'GET')
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
        $response = curl_exec($client);
        curl_close($client);
        return $response;
    }

    public function valid($openId)
    {
        //  $client = new Client();
        //        $client->load([
        //            'oauth_server' => static::SERVER_NAME,
        //            'open_id' => $openId
        //        ]);
        $collection = new clientCollection();
        $collection->where(['oauth_server' => static::SERVER_NAME, 'open_id' => $openId]);
        if (count($collection) > 0) {
            return $collection[0]['customer_id'];
        } else {
            return 0;
        }
    }

    public function available()
    {
        $config = $this->getContainer()->get('config');
        return $config['oauth/' . static::SERVER_NAME . '/enable'] &&
                $config['oauth/' . static::SERVER_NAME . '/appid'] &&
                $config['oauth/' . static::SERVER_NAME . '/secret'];
    }
}
