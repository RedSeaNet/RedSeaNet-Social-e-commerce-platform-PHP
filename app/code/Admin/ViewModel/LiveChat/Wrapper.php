<?php

namespace Redseanet\Admin\ViewModel\LiveChat;

use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\ViewModel\Template;

class Wrapper extends Template
{
    protected $sessions = null;

    public function getWsUrl()
    {
        $uri = $this->getRequest()->getUri();
        $config = $this->getConfig();
        return ($uri->getScheme() === 'https' ? 'wss:' : 'ws:') . $uri->withScheme('')
                        ->withFragment('')
                        ->withQuery('')
                        ->withPort($config['livechat/port'] ?: $uri->getPort())
                        ->withPath($config['livechat/path']);
    }

    public function getSessions()
    {
        if (is_null($this->sessions)) {
            $collection = new Customer();
            $collection->join('livechat_session', 'livechat_session.customer_2=id', [], 'left')
                    ->where(['livechat_session.customer_1' => null]);
            $collection->load(true, true);
            $this->sessions = [];
            $collection->walk(function ($item) {
                $this->sessions[] = [
                    'id' => '0-' . $item['id'],
                    'name' => $item['username'],
                    'ratings' => 0,
                    'avatar' => empty($item['avatar']) ? $this->getPubUrl('frontend/images/placeholder.png') : $this->getBaseUrl('pub/upload/customer/avatar/' . $item['avatar']),
                    'link' => '#modal-history'
                ];
            });
        }
        return $this->sessions;
    }
}
