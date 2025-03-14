<?php

namespace Redseanet\Retailer\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Lib\Bootstrap;
use Redseanet\Retailer\Model\Retailer as Model;
use Redseanet\Retailer\Model\Collection\Retailer as RetailerCollection;

class Store extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;

    /**
     * @param string $id
     * @param string $token
     * @param array $conditionData
     * @param int $languageId
     * @return array
     */
    public function getStoreList($id, $token, $conditionData, $languageId = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $collection = new RetailerCollection();
        $collection->join('core_store', 'retailer.store_id=core_store.id', ['code', 'name'], 'left');
        $collection->where(["status" => 1]);

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
        if ($conditionData['page'] > 1) {
            $collection->order('id DESC')->limit($conditionData['limit'])->offset((int) ($conditionData['page'] - 1) * $conditionData['limit']);
        } else {
            $collection->order('id DESC')->limit($conditionData['limit'])->offset(0);
        }
        $total = $collection->count();
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
        $resultData["stores"] = [];
        if (count($collection) > 0) {
            foreach ($collection as $store) {
                $profile = $this->getPubUrl('frontend/images/placeholder.png');
                if (!empty($store["profile"])) {
                    $profile = $this->getUploadedUrl('store/' . $store['store_id'] . '/' . $store["profile"]);
                }
                $store["profile"]=$profile;
                $store["contact"]= json_decode($store["contact"],true);
                $resultData["stores"][] = $store->toArray();
            }
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get page successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '404', 'data' => [], 'message' => 'not fount the page with key:' . $uriKey];
            return $this->responseData;
        }
    }

}
