<?php

namespace Redseanet\RewardPoints\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Laminas\Db\Sql\Predicate\In;
use Redseanet\Lib\Bootstrap;
use Redseanet\Customer\Model\Customer;
use Redseanet\RewardPoints\Model\Record;
use Redseanet\RewardPoints\Model\Collection\Record as collectionRecord;
use Redseanet\Lib\Model\Language;

class RewardPoints extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Translate;

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param array $condition
     * @param int $languageId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getRewardPointsList($id, $token, $customerId, $condition = [], $languageId = 0, $page = 1, $limit = 20)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $conditionKey = ['id', 'customer_id', 'category_id', 'language_id', 'product_id', 'poll_id', 'anonymous', 'status', 'uri_key', 'title', 'description', 'content', 'temp_content', 'images', 'like', 'dislike', 'reviews', 'collections', 'can_review', 'is_top', 'is_hot', 'is_draft', 'is_relate', 'created_at', 'updated_at'];
        $language = new Language();
        $language->load($languageId);
        $collectionRecord = new collectionRecord();
        $collectionRecord->where(['customer_id' => intval($customerId)]);
        if (is_array($condition) && count($condition) > 0) {
            foreach ($condition as $conditionK => $conditionV) {
                if (in_array($conditionK, $conditionKey)) {
                    $collectionRecord->where([$conditionK => $conditionV]);
                }
            }
        }
        $collectionRecord->offset(($page > 0 ? ($page - 1) : 0) * $limit)->limit((int) $limit);
        $collectionRecord->order('reward_points.created_at DESC');
        $collectionRecord->load(true, true);
        $resultData = [];
        for ($i = 0; $i < count($collectionRecord); $i++) {
            $reward = $collectionRecord[$i];
            $status_string = $reward['status'] == 0 ? 'Unavailable' : ($reward['status'] == -1 ? 'Refunded' : 'Available');
            $reward['status_string'] = $this->translate($status_string, [], null, $language['code']);
            $reward['comment_string'] = $this->translate($reward['comment'], [], null, $language['code']);
            $resultData[] = $reward;
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get reward points list successfully'];
        return $this->responseData;
    }
}
