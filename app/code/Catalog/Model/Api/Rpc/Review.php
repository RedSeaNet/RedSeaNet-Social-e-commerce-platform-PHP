<?php

namespace Redseanet\Catalog\Model\Api\Rpc;
use Redseanet\Lib\Bootstrap;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Catalog\Model\Product\Review as Model;
use Redseanet\Catalog\Model\Collection\Product\Review as Collection;

class Review extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url,\Redseanet\Lib\Traits\Translate;

    public function getReviews($id, $token, $productId, $whetherInquiries = false, $conditionData = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $resultData = [];
        $reviews = new Collection($languageId);
        $reviews->join("customer_1_index", "review.customer_id=customer_1_index.id", ["username"], "left");
        if ($whetherInquiries) {
            $reviews->where([
                'product_id' => intval($productId),
                'order_id' => null
            ])->where->isNotNull('reply');
        } else {
            $reviews->where(['review.product_id' => intval($productId)])->where->isNotNull('order_id');
        }
        if (isset($conditionData['status'])) {
            $reviews->where(['review.status' => intval($conditionData['status'])]);
        }
        if (!isset($conditionData['limit']) || $conditionData['limit'] == '') {
            $conditionData['limit'] = 20;
        } else {
            $conditionData['limit'] = intval($conditionData['limit']);
        }
        if (!isset($conditionData['page']) || $conditionData['page'] == '') {
            $conditionData['page'] = 1;
        } else {
            $conditionData['page'] = intval($conditionData['page']);
        }
        $total = $reviews->count();
        $last_page = ceil($total / $conditionData['limit']);
        $resultData['pagination'] = [
            "total" => $total,
            "per_page" => $conditionData['limit'],
            "current_page" => $conditionData['page'],
            "last_page" => $last_page,
            "next_page" => ($last_page > $conditionData['page'] ? $conditionData['page'] + 1 : $last_page),
            "previous_page" => ($conditionData['page'] > 1 ? $conditionData['page'] - 1 : 1),
            "has_next_page" => ($last_page > $conditionData['page'] ? true : false),
            "has_previous_page" => ($conditionData['page'] > 1 && $last_page > 1 ? true : false)
        ];
        if ($conditionData['page'] > 1) {
            $reviews->order('created_at DESC')->limit($conditionData['limit'])->offset((int) ($conditionData['page'] - 1) * $conditionData['limit']);
        } else {
            $reviews->order('created_at DESC')->limit($conditionData['limit'])->offset(0);
        }
        $resultData["reviews"] = [];
        if (count($reviews) > 0) {
            foreach ($reviews as $review) {
                $resultData["reviews"][] = $review->toArray();
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get review data successfully'];
        return $this->responseData;
    }

    public function createReview($id, $token, $customerId, $productId, $data = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $config = $this->getContainer()->get('config');
        $_data = [
            'product_id' => (int) $productId,
            'customer_id' => (int) $customerId,
            'order_id' => !empty($data['order_id'])?$data['order_id']:"",
            'language_id' => (int) $languageId,
            'subject' => $data['subject'],
            'content' => !empty($data['content']) ? htmlspecialchars($data['content']) : '',
            'reply' => !empty($data['reply']) ? htmlspecialchars($data['reply']) : '',
            'images' => !empty($data['images']) ? $data['images'] : '',
            'anonymous' => !empty($data['anonymous']) ? intval($data['anonymous']) : '',
            'status' => (int) $config['catalog/review/status']
        ];
        $review = new Model($_data);
        try {
            $review->save();
            //$this->responseData['data'] = $review->toArray();
            $this->responseData['statusCode'] = 200;
            $this->responseData['message'] = $this->translate('We have received your ' . (empty($data['order_id']) ? 'inquiry' : 'review') . '.');
        } catch (Exception $e) {
            $this->responseData['statusCode'] = 500;
            $this->responseData['message'] = $this->translate('An error detected. Please contact us or try again later.');
        }
        return $this->responseData;
    }

}
