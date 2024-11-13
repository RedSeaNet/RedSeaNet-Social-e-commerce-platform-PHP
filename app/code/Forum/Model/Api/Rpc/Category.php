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

class Category extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Url;

    public function getForumCategory($id, $token, $conditionData = [], $languageId = 0)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
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
        $categories->load(true, true);
        $this->responseData = ['statusCode' => '200', 'data' => $categories->toArray(), 'message' => 'get post category data successfully'];
        return $this->responseData;
    }
}
