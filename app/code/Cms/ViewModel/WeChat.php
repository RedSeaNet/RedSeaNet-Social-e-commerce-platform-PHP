<?php

namespace Redseanet\Cms\ViewModel;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Template;
use Laminas\Math\Rand;

class WeChat extends Template
{
    public function render()
    {
        if (Bootstrap::isMobile() && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false && $this->getConfig()['oauth/wechat/mp_appid']) {
            return parent::render();
        } else {
            return '';
        }
    }

    public function getConfigJson()
    {
        $params = [
            'appId' => $this->getConfig()['oauth/wechat/mp_appid'],
            'timestamp' => time(),
            'nonceStr' => Rand::getString(8),
            'jsApiList' => [
                'onMenuShareTimeline',
                'onMenuShareAppMessage',
                'onMenuShareQQ',
                'onMenuShareWeibo',
                'onMenuShareQZone'
            ]
        ];
        $params['signature'] = $this->getSignature($params);
        return json_encode($params);
    }

    public function getSignature($params)
    {
        $cache = $this->getContainer()->get('cache');
        $ticket = $cache->fetch('jsapi_ticket');
        if (!$ticket) {
            $client = curl_init('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->getConfig()['oauth/wechat/mp_appid'] . '&secret=' . $this->getConfig()['oauth/wechat/mp_secret']);
            curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($client, CURLOPT_HEADER, 0);
            $token = json_decode(curl_exec($client), true)['access_token'];
            curl_close($client);
            $client = curl_init('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $token . '&type=jsapi');
            curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($client, CURLOPT_HEADER, 0);
            $ticket = json_decode(curl_exec($client), true)['ticket'];
            curl_close($client);
            $cache->save('jsapi_ticket', $ticket, '', 7200);
        }
        $uri = $this->getRequest()->getUri();
        $uri->withFragment('');
        return sha1('jsapi_ticket=' . $ticket . '&noncestr=' . $params['nonceStr'] . '&timestamp=' . $params['timestamp'] . '&url=' . $uri->__toString());
    }
}
