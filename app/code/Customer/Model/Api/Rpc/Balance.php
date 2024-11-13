<?php

namespace Redseanet\Customer\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Customer\Exception\InvalidSmsCodeException;
use Redseanet\Lib\Model\Language;
use Redseanet\Oauth\Model\Api\Soap\Oauth;
use Laminas\Db\Sql\Where;
use Redseanet\Lib\Bootstrap;
use Redseanet\Api\Model\Rpc\{
    User
};
use Laminas\Db\Sql\Expression;
use Redseanet\Lib\Session\Segment;
use Redseanet\Customer\Model\Collection\Balance as balanceCollection;

class Balance extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Lib\Traits\Url;
    use \Redseanet\Lib\Traits\Translate;

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param int $page
     * @param int $limit
     * @param int $languageId
     * @return array
     */
    public function balanceList($id, $token, $customerId, $condition = [], $page = 1, $limit = 20, $languageId = 0, $currencyCode = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $conditionKey = ['id', 'customer_id', 'order_id', 'amount', 'comment', 'status', 'additional'];

        $language = new Language();
        $language->load($languageId);
        $balance = new balanceCollection();
        $balance->columns(['amount' => new Expression('sum(amount)')])
                ->where([
                    'customer_id' => $customerId,
                    'status' => 1
                ]);
        $balance->load(false, true);
        $totalBalance = (count($balance) ? $balance[0]['amount'] : 0);
        $collection = new balanceCollection();
        $collection->where([
            'customer_id' => $customerId,
            'status' => 1
        ]);
        if (count($condition) > 0) {
            foreach ($condition as $conditionDataK => $conditionDataV) {
                if (in_array($conditionDataK, $conditionKey)) {
                    $collection->where([$conditionDataK => $conditionDataV]);
                }
            }
        }
        $collection->offset(($page > 0 ? ($page - 1) : 0) * $limit)->limit((int) $limit);
        $collection->order('created_at DESC');
        $collection->load(true, true);
        $lists = [];
        if (count($collection) > 0) {
            for ($b = 0; $b < count($collection); $b++) {
                $list = $collection[$b];
                $status_string = $list['status'] == 1 ? 'Successful Trade' : 'Unavailable';
                $list['status_string'] = $this->translate($status_string, [], null, $language['code']);
                $list['comment_string'] = $this->translate($list['comment'], [], null, $language['code']);
                $lists[] = $list;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => ['total' => round(floatval($totalBalance), 2), 'list' => $lists], 'message' => 'get balance list'];
        return $this->responseData;
    }
}
