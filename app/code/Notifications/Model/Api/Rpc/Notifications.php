<?php

namespace Redseanet\Notifications\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Notifications\Model\Collection\Notifications as Collection;
use Redseanet\Notifications\Model\Notifications as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Language;
use DateTime;

class Notifications extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Filter;
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\Url;
    use \Redseanet\Lib\Traits\Translate;

    protected $current = null;

    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function getNotificationsList($id, $token, $customerId, $condition = [], $lastId = 0, $appType = 0, $languageId = 0)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $language = Bootstrap::getLanguage();
            $languageId = $language->getId();
        } else {
            $language = new Language();
            $language->load($languageId);
        }
        $collection = new Collection();
        $collection->join('customer_1_index', 'customer_1_index.id=core_notifications.sender_id', ['username', 'avatar'], 'left');
        if (count($condition) > 0) {
            foreach ($condition as $key => $value) {
                if ($key != 'page') {
                    $collection->where('core_notifications.' . $key . '=' . $value);
                }
            }
        }
        $collection->where('core_notifications.customer_id=' . $customerId);
        if ($lastId != 0) {
            $collection->where('core_notifications.id<=' . $lastId);
        }
        $collection->order('core_notifications.created_at desc')->offset(0)->limit(30);
        //echo $collection->getSqlString($this->getContainer()->get("dbAdapter")->getPlatform());
        $collection->load(true, true);
        $resultData = [];
        for ($i = 0; $i < count($collection); $i++) {
            if (!empty($collection[$i]['avatar'])) {
                $collection[$i]['avatar'] = $this->getUploadedUrl('customer/avatar/' . $collection[$i]['avatar']);
            } else {
                $collection[$i]['avatar'] = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
            }
            $collection[$i]['params'] = json_decode($collection[$i]['params'], true);
            $collection[$i]['created_at'] = $this->getTime($collection[$i]['created_at'], $language);
            $resultData[] = $collection[$i];
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get notifications list successfully'];
        return $this->responseData;
    }

    public function getTime($time, $language)
    {
        $dt = new DateTime($time);
        $days = $dt->diff($this->getCurrent())->format('%a');
        if ($days && $days > 1) {
            return $this->translate('%d Days Ago', [$days], null, $language['code']);
        } elseif ((int) $days == 1) {
            return $this->translate('Yesterday %d', [$dt->format('H')], null, $language['code']);
        } else {
            return $this->translate('Today', [], null, $language['code']) . $dt->format('H:i');
        }
    }

    protected function getCurrent()
    {
        if (is_null($this->current)) {
            $this->current = new DateTime();
        }
        return $this->current;
    }
}
