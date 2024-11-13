<?php

namespace Redseanet\Oauth\Model\Client;

use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;

class Twitter extends AbstractClient
{
    public const SERVER_NAME = 'twitter';

    private $signatureMethod = 'HMAC-SHA1';
    private $oauthVersion = '1.0';
    private $http_status = '';

    public function redirect($state)
    {
        $requestResponse = $this->getRequestToken($state);
        $authUrl = 'https://api.twitter.com/oauth/authenticate';
        $redirectUrl = $authUrl . '?oauth_token=' . $requestResponse['request_token'] . '&state=' . $state;
        return $redirectUrl;
    }

    public function access($token, $oauthVerifier = '', $oauth_token_secret = '')
    {
        $config = $this->getContainer()->get('config');
        $url = 'https://api.twitter.com/oauth/access_token';
        $oauthPostData = [
            'oauth_verifier' => $oauthVerifier
        ];
        $params = [
            'skip_status' => 'false',
            'oauth_consumer_key' => $config['oauth/twitter/appid'],
            'oauth_nonce' => $this->getToken(42),
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => time(),
            'oauth_token' => $token,
            'oauth_version' => $this->oauthVersion
        ];
        $params['oauth_signature'] = $this->createSignature('POST', $url, $params, $oauth_token_secret);
        $oauthHeader = $this->generateOauthHeader($params);
        $response = $this->curlHttp('POST', $url, $oauthHeader, $oauthPostData);
        //var_dump($response);
        //echo '---------access---------';
        $this->getContainer()->get('log')->logException(new \Exception($url . ':' . json_encode($response)));
        $responseVariables = [];
        parse_str($response, $responseVariables);
        $user_id = '';
        if (isset($responseVariables['user_id']) && $responseVariables['user_id'] != '') {
            $user_id = $responseVariables['user_id'];
        }
        $this->getContainer()->get('log')->logException(new \Exception('responseVariables:' . json_encode($responseVariables)));
        return ['oauth_token' => $responseVariables['oauth_token'], 'user_id' => $user_id, 'oauth_token_secret' => $responseVariables['oauth_token_secret']];
    }

    public function getInfo($token, $openId, $oauthVerifier = '', $oauth_token_secret = '')
    {
        $config = $this->getContainer()->get('config');
        $url = 'https://api.twitter.com/1.1/account/verify_credentials.json?include_email=true';
        //echo 'getInfo:'.$oauth_token_secret;
        $params = [
            'include_entities' => 'false',
            'include_email' => 'true',
            'skip_status' => 'true',
            'oauth_consumer_key' => $config['oauth/twitter/appid'],
            'oauth_nonce' => $this->getToken(42),
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => time(),
            'oauth_token' => $token,
            'oauth_version' => $this->oauthVersion
        ];

        $twitterOAuth = new TwitterOAuth($config['oauth/twitter/appid'], $config['oauth/twitter/secret'], $token, $oauth_token_secret);

        // Let's get the user's info with email
        $twitterUser = $twitterOAuth->get('account/verify_credentials', ['include_entities' => 'false', 'include_email' => 'true', 'skip_status' => 'true', ]);

        $this->getContainer()->get('log')->logException(new \Exception('twitterUser:' . json_encode($twitterUser)));
        $user_response = [];
        $user_response['oauth_email'] = $twitterUser->email;
        $user_response['oauth_open_id'] = $openId;
        $user_response['oauth_avatar'] = $twitterUser->profile_image_url;
        return $user_response;
    }

    public function getRequestToken($state)
    {
        $config = $this->getContainer()->get('config');
        $url = 'https://api.twitter.com/oauth/request_token';
        $params = [
            'oauth_callback' => $this->getBaseUrl('oauth/response/') . '?server=Twitter&state=' . $state,
            'oauth_consumer_key' => $config['oauth/twitter/appid'],
            'oauth_nonce' => $this->getToken(42),
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_timestamp' => time(),
            'oauth_version' => $this->oauthVersion
        ];

        $params['oauth_signature'] = $this->createSignature('POST', $url, $params);
        $oauthHeader = $this->generateOauthHeader($params);
        $response = $this->curlHttp('POST', $url, $oauthHeader);

        $responseVariables = [];
        parse_str($response, $responseVariables);
        $this->getContainer()->get('log')->logException(new \Exception($url . ':' . json_encode($responseVariables)));
        $tokenResponse = [];
        $segment = new Segment('customer');
        $segment->set('request_token', $responseVariables['oauth_token']);
        $segment->set('oauth_token_secret', $responseVariables['oauth_token_secret']);
        $tokenResponse['request_token'] = $responseVariables['oauth_token'];
        $tokenResponse['request_token_secret'] = $responseVariables['oauth_token_secret'];
        return $tokenResponse;
    }

    public function curlHttp($httpRequestMethod, $url, $oauthHeader, $post_data = null)
    {
        $ch = curl_init();
        $headers = [
            'Authorization: OAuth ' . $oauthHeader
        ];
        $options = [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ];
        if ($httpRequestMethod == 'POST') {
            $options[CURLOPT_POST] = true;
        }
        if (!empty($post_data)) {
            $options[CURLOPT_POSTFIELDS] = $post_data;
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $response;
    }

    public function generateOauthHeader($params)
    {
        foreach ($params as $k => $v) {
            $oauthParamArray[] = $k . '="' . rawurlencode($v) . '"';
        }
        $oauthHeader = implode(', ', $oauthParamArray);
        return $oauthHeader;
    }

    public function createSignature($httpRequestMethod, $url, $params, $tokenSecret = '')
    {
        $strParams = rawurlencode(http_build_query($params));
        $baseString = $httpRequestMethod . '&' . rawurlencode($url) . '&' . $strParams;
        $signKey = $this->generateSignatureKey($tokenSecret);
        $oauthSignature = base64_encode(hash_hmac('sha1', $baseString, $signKey, true));
        return $oauthSignature;
    }

    public function generateSignatureKey($tokenSecret)
    {
        $config = $this->getContainer()->get('config');
        $signKey = rawurlencode($config['oauth/twitter/secret']) . '&';
        if (!empty($tokenSecret)) {
            $signKey = $signKey . rawurlencode($tokenSecret);
        }
        return $signKey;
    }

    public function getToken($length)
    {
        $token = '';
        $codeAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $codeAlphabet .= 'abcdefghijklmnopqrstuvwxyz';
        $codeAlphabet .= '0123456789';
        $max = strlen($codeAlphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->cryptoRandSecure(0, $max)];
        }
        return $token;
    }

    public function cryptoRandSecure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) {
            return $min; // not so random...
        }
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }
}
