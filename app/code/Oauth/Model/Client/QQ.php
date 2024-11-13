<?php

namespace Redseanet\Oauth\Model\Client;

use Redseanet\Lib\Bootstrap;

class QQ extends AbstractClient
{
    public const SERVER_NAME = 'qq';

    public function access($token)
    {
        $config = $this->getContainer()->get('config');
        parse_str($this->request(Bootstrap::isMobile() ? 'https://graph.z.qq.com/moc2/token' : 'https://graph.qq.com/oauth2.0/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $config['oauth/qq/appid'],
            'client_secret' => $config['oauth/qq/secret'],
            'code' => $token,
            'redirect_uri' => $this->getBaseUrl('oauth/response/')
        ]), $result);
        if (Bootstrap::isMobile()) {
            parse_str($this->request('https://graph.z.qq.com/moc2/me?access_token=' . $result['access_token']));
        } else {
            $openid = json_decode(substr(trim($this->request('https://graph.qq.com/oauth2.0/me?access_token=' . $result['access_token'])), 9, -2), true)['openid'];
        }
        return [$result['access_token'], $openid];
    }

    public function getInfo($token, $openId)
    {
        $config = $this->getContainer()->get('config');
        return json_decode($this->request('https://graph.qq.com/user/get_user_info?access_token=' . $token .
                        '&oauth_consumer_key=' . $config['oauth/qq/appid'] . '&openid=' . $openId), true);
    }

    public function redirect($state)
    {
        $config = $this->getContainer()->get('config');
        return 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=' . $config['oauth/qq/appid'] .
                '&redirect_uri=' . rawurlencode($this->getBaseUrl('oauth/response/')) .
                '&state=' . $state . (Bootstrap::isMobile() ? '&display=mobile' : '');
    }
}
