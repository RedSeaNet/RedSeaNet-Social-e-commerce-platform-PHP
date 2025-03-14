<?php

namespace Redseanet\Forum\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Lib\Model\Collection\Eav\Attribute\Set;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Bootstrap;
use Redseanet\Resource\Model\Resource;
use Redseanet\Forum\Model\Collection\Category as CategoryCollection;

class Category extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;

    public function getForumCategory($id, $token, $conditionData = [], $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        $searchable = ['parent_id', 'uri_key'];
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $categories = new CategoryCollection();
        $categories->where('parent_id!=0');
        $categories->withName($languageId);
        if (count($conditionData) > 0) {
            foreach ($conditionData as $conditionDataK => $conditionDataV) {
                if (in_array($conditionDataK, $searchable)) {
                    $categories->where([$conditionDataK => $conditionDataV]);
                }
            }
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
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $language = new Language();
        $language->load($languageId);
        $total = $categories->count();
        $categories->offset(($conditionData['page'] - 1) * $conditionData['limit'])->limit((int) $conditionData['limit']);
        $categories->order('sort_order DESC');
        $last_page = ceil($total / $conditionData['limit']);
        //echo $postCollection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
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
        $categoriesData = [];
        if (count($categories) > 0) {
            foreach ($categories as $category) {
                $categoriesData[] = $category->toArray();
            }
        }
        $resultData["categories"] = $categoriesData;
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get post category data successfully'];
        return $this->responseData;
    }

}
