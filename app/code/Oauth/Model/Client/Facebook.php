<?php

namespace Redseanet\Oauth\Model\Client;

use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;

class Facebook extends AbstractClient
{
    public const SERVER_NAME = 'facebook';

    public function redirect($state)
    {
        $config = $this->getContainer()->get('config');
        return 'https://www.facebook.com/v2.8/dialog/oauth?client_id=' . $config['oauth/facebook/appid'] .
                '&redirect_uri=' . rawurlencode($this->getBaseUrl('oauth/response/')) .
                '&response_type=code&scope=public_profile%2Cemail&state=' . $state;
    }

    public function access($token)
    {
        $config = $this->getContainer()->get('config');
        $result = json_decode($this->request('https://graph.facebook.com/v2.8/oauth/access_token?client_id=' . $config['oauth/facebook/appid'] .
                        '&redirect_uri=' . rawurlencode($this->getBaseUrl('oauth/response/')) .
                        '&client_secret=' . $config['oauth/facebook/secret'] . '&code=' . $token), true);
        $data = json_decode($this->request('https://graph.facebook.com/debug_token?input_token=' . $result['access_token']), true);
        return [$result['access_token'], $data['user_id']];
    }

    public function getInfo($token, $openId)
    {
        return json_decode($this->request('https://graph.facebook.com/v2.8/' . $openId . '?access_token=' . $token), true);
    }
}
