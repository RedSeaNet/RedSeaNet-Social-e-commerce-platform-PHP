<?php

namespace Redseanet\LiveChat\ViewModel;

use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\LiveChat\Model\Collection\Group;

class Wrapper extends Template
{
    protected $customerId;
    protected $sessions = null;

    public function getCustomerId()
    {
        $this->customerId = $this->getSegment('customer')->get('customer')['id'];
        return $this->customerId;
    }

    public function getWsUrl()
    {
        $uri = $this->getRequest()->getUri();
        $config = $this->getConfig();
        $chatUri = ($uri->getScheme() === 'https' ? 'wss:' : 'ws:') . $uri->withScheme('')
                        ->withFragment('')
                        ->withQuery('')
                        ->withPort($config['livechat/port'] ? intval($config['livechat/port']) : $uri->getPort())
                        ->withPath($config['livechat/path']);
        return $chatUri;
    }

    public function getSessions()
    {
        if (is_null($this->sessions)) {
            $id = $this->getCustomerId();
            $self = new Retailer();
            $self->load($id, 'customer_id');
            $collection = new Customer();
            $collection->join(['a' => 'livechat_session'], 'a.customer_1=id', [], 'left')
                    ->join(['b' => 'livechat_session'], 'b.customer_2=id', [], 'left')
                    ->where(['b.customer_1' => $id, 'a.customer_2' => $id], 'OR');
            $collection->load(true, true);
            $this->sessions = [[
                'id' => '0-' . $id,
                'name' => Bootstrap::getMerchant()['name'],
                'ratings' => 0,
                'avatar' => $this->getPubUrl('frontend/images/avatar-holderplace.jpg'),
                'link' => 'javascript:void(0);'
            ]];
            $collection->walk(function ($item) use ($id, $self) {
                $retailer = new Retailer();
                $retailer->load($item['id'], 'customer_id');
                $i = ['id' => $item['id'] > $id ? $id . '-' . $item['id'] : $item['id'] . '-' . $id];
                if ($retailer->getId()) {
                    $i['name'] = $retailer->getStore()['name'];
                    $ratings = $retailer->getRatings();
                    $i['ratings'] = 0;
                    foreach ($ratings as $rating) {
                        $i['ratings'] += $rating['value'];
                    }
                    $i['ratings'] = count($ratings);
                    //$i['avatar'] = empty($retailer['profile']) ? $this->getPubUrl('frontend/images/placeholder.png') : 'data:image/png;base64, ' . $retailer['profile'];
                    $i['avatar'] = empty($item['avatar']) ? $this->getPubUrl('frontend/images/avatar-placeholder.jpg') : $this->getBaseUrl('pub/upload/customer/avatar/' . $item['avatar']);

                    $i['link'] = $retailer->getStoreUrl();
                } else {
                    $i['name'] = $item['username'];
                    $i['ratings'] = 0;
                    $i['avatar'] = empty($item['avatar']) ? $this->getPubUrl('frontend/images/avatarplaceholder.jpg') : $this->getBaseUrl('pub/upload/customer/avatar/' . $item['avatar']);
                    $i['link'] = $self->getId() ? '#modal-history' : 'javascript:void(0);';
                }
                $this->sessions[] = $i;
            });
        }
        return $this->sessions;
    }

    public function getGroups()
    {
        $this->customerId = $this->getSegment('customer')->get('customer')['id'];
        $groups = new Group();
        $groups->join('livechat_group_member', 'id=livechat_group_member.group_id', [], 'left');
        $groups->where('livechat_group_member.customer_id=' . $this->customerId);
        $groups->load(true, true);
        $returnResult = [];
        if (count($groups) > 0) {
            foreach ($groups as $group) {
                $returnResult[] = ['id' => 'g' . $group['id'], 'name' => 'GROUP-' . $group['name'], 'avatar' => $this->getPubUrl('frontend/images/placeholder.png'), 'link' => 'javascript:void(0);', 'ratings' => 0];
            }
        }
        return $returnResult;
    }
}
