<?php

namespace Redseanet\Forum\Traits;

use Redseanet\Lib\Bootstrap;

trait Wechat
{
    public function getAccessToken()
    {
        $config = $this->getContainer()->get('config');
        $client = curl_init('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $config['oauth/wechat/mini_appid'] . '&secret=' . $config['oauth/wechat/mini_secret']);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($client, CURLOPT_HEADER, 0);
        $curlData = json_decode(curl_exec($client), true);
        $token = $curlData['access_token'] ? $curlData['access_token'] : '';
        curl_close($client);
        return $token;
    }

    protected function convertCodeToOpenId($code)
    {
        $config = $this->getContainer()->get('config');
        $appid = $config['oauth/wechat/mini_appid'];
        $appsecret = $config['oauth/wechat/mini_secret'];
        $grant_type = 'authorization_code';
        $params = 'appid=' . $appid . '&secret=' . $appsecret . '&js_code=' . $code . '&grant_type=' . $grant_type;
        $url = 'https://api.weixin.qq.com/sns/jscode2session?' . $params;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $resJson = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($resJson, true);
        return $res;
    }

    public function msgSecCheck($params)
    {
        $result = ['code' => 200, 'data' => [], 'Message' => ''];
        $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . $params['token'];
        $postdata = json_encode(['version' => 2, 'openid' => $params['openid'], 'scene' => $params['scene'], 'content' => $params['content'], 'title' => $params['title']], JSON_UNESCAPED_UNICODE);
        Bootstrap::getContainer()->get('log')->logException(new \Exception($postdata));
        $headers = ['Content-type: application/json;charset=UTF-8'];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 30000);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        $data = curl_exec($curl);
        if (curl_error($curl)) {
            $result['code'] = 400;
            $result['message'] = curl_error($curl);
        } else {
            $array_data = json_decode($data, true);
            if ($array_data['errcode'] != 0 || $array_data['result']['suggest'] != 'pass') {
                $result['code'] = $array_data['errcode'];
                $result['data'] = $array_data;
            } else {
                $result['data'] = $array_data;
            }
            curl_close($curl);
        }
        return $result;
    }

    public function getQrCode($qr_data)
    {
        $accessToken = $this->getAccessToken();
        if (empty($accessToken)) {
            return ['statusCode' => 400, 'massege' => ''];
        } else {
            $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $accessToken;
            $header = [
                'Accept: application/json',
            ];
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 6000);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($qr_data));
            $data = curl_exec($curl);
            if (curl_error($curl)) {
                //print "Error: " . curl_error($curl);
                return ['statusCode' => 400,  'massege' => curl_error($curl)];
            } else {
                curl_close($curl);
                $returnData = json_decode($data, true);
                if (isset($returnData['errcode']) && $returnData['errcode'] != 0) {
                    return ['statusCode' => 400, 'massege' => (isset($returnData['errmsg']) && $returnData['errmsg'] != '' ? $returnData['errmsg'] : 'you got some error, please try again later!'), 'errcode' => (isset($returnData['errcode']) && $returnData['errcode'] != '' ? $returnData['errcode'] : 0)];
                } else {
                    return ['statusCode' => 200, 'status' => true, 'qr' => $data];
                }
            }
        }
    }
}
