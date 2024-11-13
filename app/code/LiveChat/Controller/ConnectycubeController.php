<?php

namespace Redseanet\LiveChat\Controller;

use DOMDocument;
use DOMXPath;
use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Customer\Controller\AuthActionController;
use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\LiveChat\Exception\InvalidIdException;
use Redseanet\LiveChat\Model\{
    Collection\Record,
    Collection\Session as Collection,
    Group,
    Session,
    Status
};
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Retailer\Model\Collection\Retailer as retailerCollection;
use Redseanet\Sales\Model\Collection\Order;
use Laminas\Db\Sql\Expression;
use Laminas\Stdlib\SplQueue;
use Redseanet\Lib\Bootstrap;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Combine;

class ConnectycubeController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    use \Redseanet\LiveChat\Traits\Connectycube;

    public function indexAction()
    {
        $segment = new Segment('customer');
        $from = $segment->get('customer')['id'];
        $customer = $segment->get('customer');
        $error = '';
        $config = $this->getContainer()->get('config');
        if (($to = $this->getRequest()->getQuery('chat')) && ($from != $to)) {
            $to = intval($to);
            try {
                $id = $this->withSingle($from, $to);
            } catch (InvalidIdException $e) {
                $error = $this->translate($e->getMessage());
            }
        }

        $params = [];
        $params['application_id'] = $config['livechat/connectycube_appId'];
        $params['auth_key'] = $config['livechat/connectycube_authKey'];
        $params['authorization_secret'] = $config['livechat/connectycube_authSecret'];
        $params['email'] = $customer['email'];
        $params['id'] = $customer['id'];
        $params['username'] = $customer['username'];
        $params['login'] = $customer['username'];
        $params['password'] = $config['livechat/connectycube_userPassword'];
        $userData = $this->getSessionAndUser($params);
        if (isset($userData['data']) && isset($userData['data']['session']) && isset($userData['data']['session']['token']) && $userData['data']['session']['token'] != '') {
            $result['customer']['application_id'] = $params['application_id'];
            $result['customer']['chat_server_id'] = $userData['data']['session']['user_id'];
            $result['customer']['token'] = $userData['data']['session']['token'];
            $params['token'] = $userData['data']['session']['token'];
            $fromUserData = $userData['data']['session']['user'];
        } else {
            $tokenData = $this->getSession($params);
            $params['token'] = $tokenData['data']['session']['token'];
            if (isset($tokenData['data']) && isset($tokenData['data']['session']) && isset($tokenData['data']['session']['token']) && $tokenData['data']['session']['token'] != '') {
                $signUpData = $this->signUp($params);
                $userData = $this->getSessionAndUser($params);
                $result['customer']['application_id'] = $params['application_id'];
                $result['customer']['chat_server_id'] = $userData['data']['session']['user_id'];
                $result['customer']['token'] = $userData['data']['session']['token'];
                $params['token'] = $userData['data']['session']['token'];
                $fromUserData = $userData['data']['session']['user'];
            }
        }
        $dialogs = $this->getDialog($params);

        $createdDialogs = [];
        if (isset($dialogs['data']['items']) && count($dialogs['data']['items']) > 0) {
            $createdDialogs = $dialogs['data']['items'];
        }
        $root = $this->getLayout('livechat_connectycube');
        $self = new retailerCollection();
        $self->load($from, 'customer_id');

        $subselect1 = new Select('livechat_session');
        $subselect1->columns(['customer_2'])->where(['customer_1' => $from]);
        $subselect2 = new Select('livechat_session');
        $subselect2->columns(['customer_1'])->where(['customer_2' => $from]);
        $subselect1->combine($subselect2);
        $collection = new customerCollection();

        $collection->columns(['*']);
        $collection->in('id', $subselect1);
        //        echo $collection->getSqlString($this->getContainer()->get('dbAdapter')->getPlatform());
        //        exit;
        $collection->load(true, true);
        $sessions = [];
        $collection->walk(function ($item) use ($from, $self, &$sessions, $createdDialogs, $params, $fromUserData) {
            $i = ['id' => $item['id'] > $from ? $from . '-' . $item['id'] : $item['id'] . '-' . $from];
            $i['from'] = $from;
            $i['to'] = $item['id'];
            $i['name'] = $item['username'];
            $i['ratings'] = 0;
            $i['avatar'] = empty($item['avatar']) ? $this->getPubUrl('frontend/images/avatar-holderplace.jpg') : $this->getUploadedUrl('pub/upload/customer/avatar/' . $item['avatar']);
            $i['link'] = 'javascript:void(0);';
            $dialogData = [];
            for ($d = 0; $d < count($createdDialogs); $d++) {
                if ($createdDialogs[$d]['description'] == $i['id']) {
                    $dialogData = $createdDialogs[$d];
                }
            }
            if (!isset($dialogData['description'])) {
                $tmpParams = [];
                $tmpParams['token'] = $params['token'];
                $tmpParams['email'] = $item['email'];
                $toData = $this->getUserByEmail($tmpParams);
                $toUserData = [];
                if (isset($toData['data']) && isset($toData['data']['user'])) {
                    $toUserData = $toData['data']['user'];
                } else {
                    $toLocalData = new Customer();
                    $toLocalData->load($item['id']);
                    $currentUserParams = $params;
                    $currentUserParams['id'] = $item['id'];
                    $currentUserParams['username'] = $item['username'];
                    $currentUserParams['login'] = $item['username'];
                    $currentUserParams['email'] = $item['email'];
                    $currentCreatedUser = $this->signUp($currentUserParams);
                    $toUserData = $currentCreatedUser['data']['user'];
                }

                $createDialogParams = [];
                $createDialogParams['type'] = 3;
                $createDialogParams['name'] = '';
                $occupants_ids = [$toUserData['id'], $fromUserData['id']];
                $createDialogParams['occupants_ids'] = implode(',', $occupants_ids);
                $createDialogParams['description'] = $i['id'];
                $createDialogParams['token'] = $params['token'];
                $createDialogData = $this->createDialog($createDialogParams);
                $dialogData = $createDialogData['data'];
            }
            $messageParams = [];
            $messageParams['dialog_id'] = $dialogData['_id'];
            $messageParams['token'] = $params['token'];
            $messageParams['limit'] = 300;
            $messageParams['sort_desc'] = 'date_sent';
            //$messageParams["sort_asc"] = "date_sent";
            $hostoryMessages = $this->getMessages($messageParams);
            $i['messages'] = isset($hostoryMessages['data']) && isset($hostoryMessages['data']['items']) ? $hostoryMessages['data']['items'] : [];
            $i['chat_server_id'] = $dialogData['_id'];
            $occupants_ids = [];
            for ($n = 0; $n < count($dialogData['occupants_ids']); $n++) {
                if ($fromUserData['id'] != $dialogData['occupants_ids'][$n]) {
                    $occupants_ids[] = $dialogData['occupants_ids'][$n];
                }
            }
            $i['chat_recipient_id'] = implode(',', $occupants_ids);
            $i['last_message_date_sent'] = $dialogData['last_message_date_sent'];
            //print_r($dialogData);
            $sessions[] = $i;
        });
        //        $sessions[] = [
        //            'id' => '0-' . $from,
        //            'name' => Bootstrap::getMerchant()['name'],
        //            'ratings' => 0,
        //            'avatar' => $this->getPubUrl('frontend/images/placeholder.png'),
        //            'link' => 'javascript:void(0);'
        //        ];

        $root->getChild('livechat', true)->setVariable('sessions', $sessions);
        $root->getChild('livechat', true)->setVariable('fromUserData', $fromUserData);
        if ($error) {
            $root->getChild('messages', true)->addMessage($error, 'danger');
        } elseif (isset($id)) {
            $root->getChild('livechat', true)->setVariable('current', $id);
        }
        return $root;
    }

    protected function withSingle($from, $to)
    {
        $session = new Session();
        if ($from > $to) {
            $data = ['customer_1' => $to, 'customer_2' => $from];
            $id = $to . '-' . $from;
        } else {
            $data = ['customer_1' => $from, 'customer_2' => $to];
            $id = $from . '-' . $to;
        }
        $session->load($data);
        if (!$session->getId()) {
            $session->setData($data + ['session_id' => $data['customer_1'] . '-' . $data['customer_2']])->save([], true);
            $this->flushList('customer');
        }
        return $id;
    }

    public function prepareAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $segment = new Segment('customer');
            $customer = $segment->get('customer');
            $result = ['records' => [], 'customer' => ['id' => $customer['id'], 'username' => $customer['username']]];
            $from = $customer['id'];
            $config = $this->getContainer()->get('config');
            $collection = new Collection();
            $collection->where(['customer_1' => $from, 'customer_2' => $from], 'OR');
            $collection->load(true, true);
            $content = [];
            foreach ($collection as $item) {
                $content[] = (int) $item['customer_1'] < $item['customer_2'] ?
                        (int) $item['customer_1'] . '-' . $item['customer_2'] :
                        $item['customer_2'] . '-' . $item['customer_1'];
            }
            $params = [];
            $params['application_id'] = $config['livechat/connectycube_appId'];
            $params['auth_key'] = $config['livechat/connectycube_authKey'];
            $params['authorization_secret'] = $config['livechat/connectycube_authSecret'];
            $params['email'] = $customer['email'];
            $params['id'] = $customer['id'];
            $params['username'] = $customer['username'];
            $userData = $this->getSessionAndUser($params);
            if (isset($userData['data']) && isset($userData['data']['session']) && isset($userData['data']['session']['token']) && $userData['data']['session']['token'] != '') {
                $result['customer']['application_id'] = $params['application_id'];
                $result['customer']['chat_server_id'] = $userData['data']['session']['user_id'];
                $result['customer']['token'] = $userData['data']['session']['token'];
                $params['token'] = $userData['data']['session']['token'];
            } else {
                $tokenData = $this->getSession($params);
                $params['token'] = $tokenData['data']['session']['token'];
                if (isset($tokenData['data']) && isset($tokenData['data']['session']) && isset($tokenData['data']['session']['token']) && $tokenData['data']['session']['token'] != '') {
                    $signUpData = $this->signUp($params);
                    $userData = $this->getSessionAndUser($params);
                    $result['customer']['application_id'] = $params['application_id'];
                    $result['customer']['chat_server_id'] = $userData['data']['session']['user_id'];
                    $result['customer']['token'] = $userData['data']['session']['token'];
                    $params['token'] = $userData['data']['session']['token'];
                }
            }
            return $result;
        } else {
            return [];
        }
        return [];
    }

    public function unreadCountAction()
    {
        set_time_limit(0);
        if ($this->getRequest()->isXmlHttpRequest()) {
            $segment = new Segment('customer');
            $customer = $segment->get('customer');
            $result = [];
            $config = $this->getContainer()->get('config');
            $params = [];
            $params['application_id'] = $config['livechat/connectycube_appId'];
            $params['auth_key'] = $config['livechat/connectycube_authKey'];
            $params['authorization_secret'] = $config['livechat/connectycube_authSecret'];
            $params['email'] = $customer['email'];
            $params['id'] = $customer['id'];
            $params['username'] = $customer['username'];
            $userData = $this->getSessionAndUser($params);

            if (isset($userData['data']) && isset($userData['data']['session']) && isset($userData['data']['session']['token']) && $userData['data']['session']['token'] != '') {
                $params['token'] = $userData['data']['session']['token'];
            } else {
                $tokenData = $this->getSession($params);
                $params['token'] = $tokenData['data']['session']['token'];
                if (isset($tokenData['data']) && isset($tokenData['data']['session']) && isset($tokenData['data']['session']['token']) && $tokenData['data']['session']['token'] != '') {
                    $signUpData = $this->signUp($params);
                    $userData = $this->getSessionAndUser($params);
                    $params['token'] = $userData['data']['session']['token'];
                }
            }
            $dialogs = $this->getDialog($params);
            $dialogsId = [];
            if (isset($dialogs['data']['items']) && count($dialogs['data']['items']) > 0) {
                for ($i = 0; $i < count($dialogs['data']['items']); $i++) {
                    $dialogsId[] = $dialogs['data']['items'][$i]['_id'];
                }
            }
            $params['dialogs'] = $dialogsId;
            $unreadData = $this->getUnreadMessageCount($params);
            if (isset($unreadData['data'])) {
                $result = $unreadData['data'];
            }
            return $result;
        } else {
            return [];
        }
        return [];
    }

    private function getSession($params)
    {
        $tokenPostdata = [];
        $tokenPostdata['application_id'] = $params['application_id'];
        $tokenPostdata['auth_key'] = $params['auth_key'];
        $tokenPostdata['nonce'] = rand();
        $tokenPostdata['timestamp'] = time();

        $stringForSignatureToken = 'application_id=' . $tokenPostdata['application_id'] . '&auth_key=' . $tokenPostdata['auth_key'] . '&nonce=' . $tokenPostdata['nonce'] . '&timestamp=' . $tokenPostdata['timestamp'];
        $tokenPostdata['signature'] = hash_hmac('sha1', $stringForSignatureToken, $params['authorization_secret']);

        $tokenData = $this->curl_post('https://api.connectycube.com/session', $tokenPostdata);
        return $tokenData;
    }

    private function signUp($params)
    {
        $signUpPostdata = [];
        $signUpPostdata['user[login]'] = $params['username'];
        $signUpPostdata['user[password]'] = $params['password'];
        $signUpPostdata['user[email]'] = $params['email'];
        $signUpPostdata['user[external_user_id]'] = $params['id'];
        $signUpPostdata['user[full_name]'] = $params['username'];
        $signUpData = $this->curl_post('https://api.connectycube.com/users', $signUpPostdata, $params['token']);
        return $signUpData;
    }

    private function createDialog($params)
    {
        //'{"type":2,"name":"Friday party","occupants_ids":"29085,29086,29087","description":"lets dance the night away","photo":"party.jpg"}'
        $createDialogPostdata = [];
        $createDialogPostdata['type'] = $params['type'];
        $createDialogPostdata['name'] = $params['name'];
        $createDialogPostdata['occupants_ids'] = $params['occupants_ids'];
        $createDialogPostdata['description'] = $params['description'];
        $createDialogData = $this->curl_post('https://api.connectycube.com/chat/Dialog', $createDialogPostdata, $params['token']);
        return $createDialogData;
    }

    private function getUserByExternalId($params)
    {
        $userPostdata = [];
        $userData = $this->curl_get('https://api.connectycube.com/users/external/' . $params['id'], $userPostdata, $params['token']);
        return $userData;
    }

    private function getUserByEmail($params)
    {
        $userPostdata = [];
        $userPostdata['email'] = $params['email'];
        $userData = $this->curl_get('https://api.connectycube.com/users/by_email', $userPostdata, $params['token']);
        return $userData;
    }

    private function getMessages($params)
    {
        $userPostdata = [];
        $userPostdata['chat_dialog_id'] = $params['dialog_id'];
        $userPostdata['limit'] = isset($params['limit']) ? intval($params['limit']) : 200;
        if (isset($params['sort_desc']) && $params['sort_desc'] != '') {
            $userPostdata['sort_desc'] = $params['sort_desc'];
        }
        if (isset($params['sort_asc']) && $params['sort_asc'] != '') {
            $userPostdata['sort_asc'] = $params['sort_asc'];
        }
        $userPostdata['mark_as_read'] = 1;
        $messagesData = $this->curl_get('https://api.connectycube.com/chat/Message', $userPostdata, $params['token']);
        return $messagesData;
    }
}
