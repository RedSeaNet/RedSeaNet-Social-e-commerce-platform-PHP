<?php

namespace Redseanet\Oauth\Model\Client;

class Wechat extends AbstractClient
{
    public const SERVER_NAME = 'wechat';

    private $info = [];

    public function redirect($state)
    {
        $config = $this->getContainer()->get('config');
        $flag = strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false;
        return ($flag ?
                ('https://open.weixin.qq.com/connect/qrconnect?appid=' . $config['oauth/wechat/appid'] .
                '&redirect_uri=' . rawurlencode($this->getBaseUrl('oauth/response/')) .
                '&response_type=code&scope=snsapi_login&state=' . $state .
                '#wechat_redirect') :
                ('https://open.weixin.qq.com/connect/oauth2/authorize?appid=') . $config['oauth/wechat/mp_appid'] .
                '&redirect_uri=' . rawurlencode($this->getBaseUrl('oauth/response/')) .
                '&response_type=code&scope=snsapi_userinfo&state=' . $state .
                '#wechat_redirect');
    }

    public function access($token)
    {
        $config = $this->getContainer()->get('config');
        $flag = strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false;
        $result = json_decode($this->request('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $config['oauth/wechat/' . ($flag ? '' : 'mp_') . 'appid'] .
                        '&secret=' . $config['oauth/wechat/' . ($flag ? '' : 'mp_') . 'secret'] . '&code=' . $token . '&grant_type=authorization_code'), true);
        $this->info = json_decode($this->request('https://api.weixin.qq.com/sns/userinfo?access_token=' . $result['access_token'] . '&openid=' . $result['openid']), true);
        return [$result['access_token'], !empty($result['unionid']) && strlen($result['unionid']) > 5 ? $result['unionid'] : $this->info['unionid']];
    }

    public function getInfo($token, $openId)
    {
        return $this->info;
    }
}
