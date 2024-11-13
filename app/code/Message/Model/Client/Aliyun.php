<?php

namespace Redseanet\Message\Model\Client;

use Laminas\Math\Rand;

class Aliyun extends AbstractClient
{
    public static $code = 'aliyun';

    public const NAME = 'Aliyun';

    public function send($phone, $code, $params = null)
    {
        $config = $this->getContainer()->get('config');
        if (!$code && !$config['message/general/enable'] && !$config['message/aliyun/signature']) {
            return false;
        }
        $iterator = (array) $phone;
        $phones = '';
        $count = 0;
        while ($p = array_shift($iterator)) {
            $phones .= $p . ',';
            if (++$count >= 1000) {
                break;
            }
        }
        $old = date_default_timezone_get();
        date_default_timezone_set('GMT');
        $body = [
            'PhoneNumbers' => rtrim($phones, ','),
            'SignName' => $config['message/aliyun/signature'],
            'TemplateCode' => $code,
            'TemplateParam' => json_encode($params),
            'Action' => 'SendSms',
            'Timestamp' => date('Y-m-d\TH:i:s\Z'),
            'RegionId' => 'cn-hangzhou',
            'Version' => '2017-05-25',
            'AccessKeyId' => $config['message/aliyun/appid'],
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'SignatureNonce' => Rand::getString(36, 'abcdef0123456789-')
        ];
        date_default_timezone_set($old);
        $body['Signature'] = $this->sign($body);
        $this->request($config['message/aliyun/gateway'], http_build_query($body), 'POST');
        if (!empty($iterator)) {
            $this->send($iterator, $code, $params);
        }
        return true;
    }

    public function sign($params = [])
    {
        $config = $this->getContainer()->get('config');
        ksort($params);
        $data = '';
        foreach ($params as $key => $value) {
            $data .= '&' . $this->encode($key) . '=' . $this->encode($value);
        }
        return base64_encode(hash_hmac('sha1', 'POST&%2F&' . $this->encode(substr($data, 1)), $config['message/aliyun/secret'] . '&', true));
    }

    private function encode($str)
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], urlencode($str));
    }
}
