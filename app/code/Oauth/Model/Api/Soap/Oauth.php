<?php

namespace Redseanet\Oauth\Model\Api\Soap;

use Redseanet\Api\Model\Api\AbstractHandler;
use Redseanet\Oauth\Model\Client;
use Redseanet\Oauth\Model\Collection\Client as Collection;

class Oauth extends AbstractHandler
{
    /**
     * @param string $sessionId
     * @param string $serverName
     * @param string $openId
     * @return int
     */
    public function oauthLogin($sessionId, $serverName, $openId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $client = new Client();
        $client->load([
            'oauth_server' => $serverName,
            'open_id' => $openId
        ]);
        return $client->getId() ?: 0;
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @param string $serverName
     * @param string $openId
     */
    public function oauthBind($sessionId, $customerId, $serverName, $openId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $client = new Client();
        $client->setData([
            'customer_id' => $customerId,
            'oauth_server' => $serverName,
            'open_id' => $openId
        ])->save();
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function oauthBindedServer($sessionId, $customerId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $collection = new Collection();
        $collection->columns(['oauth_server'])
                ->where(['customer_id' => $customerId]);
        $result = [];
        $collection->walk(function ($item) use (&$result) {
            $result[] = $item['oauth_server'];
        });
        return $result;
    }
}
