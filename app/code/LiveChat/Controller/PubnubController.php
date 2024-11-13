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
use PubNub\PNConfiguration;
use PubNub\PubNub;
use PubNub\Exceptions\PubNubConnectionException;
use PubNub\Exceptions\PubNubServerException;
use PubNub\Exceptions\PubNubException;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Combine;

class PubnubController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        $segment = new Segment('customer');
        $from = $segment->get('customer')['id'];
        $error = '';
        $sessions = [];
        $config = $this->getContainer()->get('config');
        if (($to = $this->getRequest()->getQuery('chat')) && ($from != $to)) {
            try {
                $to = intval($to);
                $id = $this->withSingle($from, $to);
            } catch (InvalidIdException $e) {
                $error = $this->translate($e->getMessage());
            }
        }
        try {
            $pnConfiguration = new PNConfiguration();
            $pnConfiguration->setSubscribeKey($config['livechat/pubnub_subscribeKey']); // required
            $pnConfiguration->setPublishKey($config['livechat/pubnub_publishKey']);
            $pnConfiguration->setSecretKey($config['livechat/pubnub_secretKey']);
            $pnConfiguration->setUuid($from);
            $pnConfiguration->setSecure(true);
            $pnConfiguration->setConnectTimeout(10);
            $pnConfiguration->setSubscribeTimeout(310);
            $pnConfiguration->setNonSubscribeRequestTimeout(300);

            $pubnub = new PubNub($pnConfiguration);
        } catch (PubNubServerException $e) {
            $this->getContainer()->get('log')->logException($e);
        }
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
        $collection->load(true, true);
        $collection->walk(function ($item) use ($from, $self, &$sessions, $pubnub) {
            $i = ['id' => $item['id'] > $from ? $from . '-' . $item['id'] : $item['id'] . '-' . $from];
            $i['from'] = $from;
            $i['to'] = $item['id'];
            $i['name'] = $item['username'];
            $i['ratings'] = 0;
            $i['avatar'] = empty($item['avatar']) ? $this->getPubUrl('frontend/images/avatar-holderplace.jpg') : $this->getUploadedUrl('pub/upload/customer/avatar/' . $item['avatar']);
            $i['link'] = 'javascript:void(0);';
            $messages = [];
            try {
                $historyObject = $pubnub->history()->channel($i['id'])->includeTimetoken(true)->count(100)->sync();
                $pubnubMessages = $historyObject->getMessages();
                if (is_array($pubnubMessages) && count($pubnubMessages) > 0) {
                    for ($m = 0; $m < count($pubnubMessages); $m++) {
                        $message = $pubnubMessages[$m]->getEntry();
                        $message['timetoken'] = $pubnubMessages[$m]->getTimetoken();
                        $message['time'] = date('Y-m-d h:i:s', substr($pubnubMessages[$m]->getTimetoken(), 0, (strlen($pubnubMessages[$m]->getTimetoken()) - 7)));
                        $messages[] = $message;
                    }
                }
            } catch (PubNubServerException $e) {
                $this->getContainer()->get('log')->logException($e);
            }
            $last_message_date_sent = 0;
            if (count($messages) > 0) {
                $last_message_date_sent = strtotime($messages[0]['time']);
            }
            $i['last_message_date_sent'] = $last_message_date_sent;
            $i['messages'] = $messages;
            $sessions[] = $i;
        });
        $root = $this->getLayout('livechat_pubnub');
        $root->getChild('livechat', true)->setVariable('sessions', $sessions);
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
            //            try {
            //
            //                $pnConfiguration = new PNConfiguration();
            //
            //// subscribeKey from Admin Portal
            //                $pnConfiguration->setSubscribeKey("sub-c-c3dcce0a-6cfe-11ec-a2db-9eb9413efc82"); // required
            //// publishKey from Admin Portal (only required if publishing)
            //                $pnConfiguration->setPublishKey("pub-c-d7caf430-fb5b-4155-ab57-78c7a4cdca84");
            //
            //// secretKey (only required for modifying/revealing access permissions)
            //                $pnConfiguration->setSecretKey("sec-c-OTQ2NTk2MTMtZDk4MC00MmUwLThhMmQtZWQ1YmJkYzY4M2Ix");
            //
            //// if cipherKey is passed, all communicatons to/from pubnub will be encrypted
            //                //$pnConfiguration->setCipherKey("my_cipherKey");
            //
            //// UUID to be used as a device identifier, a default UUID is generated
            //// if not passsed
            //                $pnConfiguration->setUuid($from);
            //
            //// if Access Manager is utilized, client will use this authKey in all restricted
            //// requests
            ////$pnConfiguration->setAuthKey("my_auth_key");
            //// use SSL (enabled by default)
            //                $pnConfiguration->setSecure(true);
            //
            //// how long to wait before giving up connection to client
            //                $pnConfiguration->setConnectTimeout(10);
            //
            //// how long to keep the subscribe loop running before disconnect
            //                $pnConfiguration->setSubscribeTimeout(310);
            //
            //// on non subscribe operations, how long to wait for server response
            //                $pnConfiguration->setNonSubscribeRequestTimeout(300);
            //
            //                $pubnub = new PubNub($pnConfiguration);
            //
            //                $pubnub->subscribe()
            //                        ->channels($id)
            //                        ->execute();
            //
            //                $pubnub->publish()
            //                        ->channel($id)
            //                        ->message("Hello!")
            //                        ->sync();
            //            } catch (PubNubServerException $e) {
            //
            //                $this->getContainer()->get('log')->logException($e);
            //            }
        }
        return $id;
    }

    public function prepareAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $segment = new Segment('customer');
            $customer = $segment->get('customer');
            $from = $customer['id'];
            $collection = new Collection();
            $collection->where(['customer_1' => $from, 'customer_2' => $from], 'OR');
            $collection->load(true, true);
            $content = [];
            foreach ($collection as $item) {
                $content[] = (int) $item['customer_1'] < $item['customer_2'] ?
                        (int) $item['customer_1'] . '-' . $item['customer_2'] :
                        $item['customer_2'] . '-' . $item['customer_1'];
            }
            return ['records' => [], 'customer' => ['id' => $customer['id'], 'username' => $customer['username']]];
        } else {
            return [];
        }
        return [];
    }
}
