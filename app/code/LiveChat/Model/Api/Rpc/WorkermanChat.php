<?php

namespace Redseanet\LiveChat\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Model\Eav\Attribute\Set;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\LiveChat\Model\Collection\GroupCollection;
use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Bootstrap;
use Redseanet\LiveChat\Model\Collection\Record;
use Redseanet\LiveChat\Model\Collection\Session as Collection;
use Redseanet\LiveChat\Model\Group;
use Redseanet\LiveChat\Model\Session;
use Redseanet\LiveChat\Model\Status;
use Redseanet\Customer\Model\Customer as customerModel;

class WorkermanChat extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Url;
    use \Redseanet\LiveChat\Traits\Workman;

    public function prepareWorkermanChat($id, $token, $customerId)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $resultData = ['records' => $this->getRecordsWorkermanChat($customerId), 'sessions' => $this->getSessionsWorkermanChat($customerId)];
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get chat prepare information successfully'];
        return $this->responseData;
    }

    public function getSessionsWorkermanChat($customerId)
    {
        $id = $customerId;
        $self = new Retailer();
        $self->load($id, 'customer_id');
        $collection = new Customer();
        $collection->join(['a' => 'livechat_session'], 'a.customer_1=id', [], 'left')
                ->join(['b' => 'livechat_session'], 'b.customer_2=id', [], 'left')
                ->where(['b.customer_1' => $id, 'a.customer_2' => $id], 'OR');
        $collection->load(true, true);

        $sessions = [[
            'id' => '0-' . $id,
            'name' => Bootstrap::getMerchant()['name'],
            'ratings' => 0,
            'avatar' => $this->getPubUrl('frontend/images/placeholder.png'),
            'link' => 'javascript:void(0);'
        ]];

        $collection->walk(function ($item) use ($id, $self, &$sessions) {
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
                $i['avatar'] = empty($item['avatar']) ? $this->getPubUrl('frontend/images/placeholder.png') : $this->getBaseUrl('pub/upload/customer/avatar/' . $item['avatar']);
                $i['link'] = $self->getId() ? '#modal-history' : 'javascript:void(0);';
            }

            $sessions[] = $i;
        });
        return $sessions;
    }

    public function startWorkermanChat($id, $token, $from, $to = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!empty($from)) {
            if (substr($from, 0, 1) === 'g') {
                $id = $this->inGroup($from, $to);
            } else {
                $id = $this->withSingle($from, $to);
            }
        }
        $sessions = $this->getSessionsWorkermanChat($from);
        $resultData = ['records' => $this->getRecordsSingerWithRedis($id), 'sessions' => $sessions, 'sessionid' => $id];
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get chat prepare information successfully'];
        return $this->responseData;
    }
}
