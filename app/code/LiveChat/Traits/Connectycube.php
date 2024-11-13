<?php

namespace Redseanet\LiveChat\Traits;

trait Connectycube
{
    private function getSessionAndUser($params)
    {
        $postdata = [];
        $postdata['application_id'] = $params['application_id'];
        $postdata['auth_key'] = $params['auth_key'];
        $postdata['nonce'] = rand();
        $postdata['timestamp'] = time();
        $postdata['user[email]'] = $params['email'];
        $postdata['user[password]'] = $params['password'];
        $stringForSignature = 'application_id=' . $postdata['application_id'] . '&auth_key=' . $postdata['auth_key'] . '&nonce=' . $postdata['nonce'] . '&timestamp=' . $postdata['timestamp'] . '&user[email]=' . $postdata['user[email]'] . '&user[password]=' . $postdata['user[password]'];
        $postdata['signature'] = hash_hmac('sha1', $stringForSignature, $params['authorization_secret']);
        $userData = $this->curl_post('https://api.connectycube.com/session', $postdata);
        return $userData;
    }

    private function getDialog($params)
    {
        $dialogPostdata = [];
        $dialogPostdata['type'] = 3;
        //$dialogPostdata["occupants_ids[in]"] = [$params["id"]];
        $dialogData = $this->curl_get('https://api.connectycube.com/chat/Dialog', $dialogPostdata, $params['token']);
        return $dialogData;
    }

    private function getUnreadMessageCount($params)
    {
        $unreadMessagePostdata = [];
        $unreadMessagePostdata['chat_dialog_ids'] = implode(',', $params['dialogs']);
        $unreadMessageData = $this->curl_get('https://api.connectycube.com/chat/Message/unread', $unreadMessagePostdata, $params['token']);
        return $unreadMessageData;
    }

    private function curl_post($url, $postdata, $token = '')
    {
        $result = ['data' => [], 'Message' => ''];
        if ($token != '') {
            $header = [
                'Accept: application/json',
                'CB-Token: ' . $token
            ];
        } else {
            $header = [
                'Accept: application/json',
            ];
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 30000);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postdata));
        $data = curl_exec($curl);
        if (curl_error($curl)) {
            //print "Error: " . curl_error($curl);
            $result['message'] = curl_error($curl);
        } else {
            //var_dump($data);
            $result['data'] = json_decode($data, true);
            curl_close($curl);
        }
        return $result;
    }

    public function curl_get($url, $postdata, $token = '')
    {
        $result = ['data' => [], 'Message' => ''];
        if ($token != '') {
            $header = [
                'Accept: application/json',
                'CB-Token: ' . $token
            ];
        } else {
            $header = [
                'Accept: application/json',
            ];
        }
        $curl = curl_init();
        $params = [];
        if (count($postdata) > 0) {
            foreach ($postdata as $k => $v) {
                $params[] = $k . '=' . $v;
            }
            $url = $url . '?' . implode('&', $params);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 30000);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($curl);
        if (curl_error($curl)) {
            //print "Error: " . curl_error($curl);
            $result['message'] = curl_error($curl);
        } else {
            //var_dump($data);
            $result['data'] = json_decode($data, true);
            curl_close($curl);
        }
        return $result;
    }
}
