<?php

namespace Redseanet\Banner\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Lib\Bootstrap;
use Redseanet\Banner\Model\Banner as Model;
use Redseanet\Banner\Model\Collection\Banner as bannerCollection;

class Banner extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Url;

    /**
     * @param string $id
     * @param string $token
     * @param string $code
     * @param int $limit
     * @param int $languageId
     * @return array
     */
    public function getBannerByCode($id, $token, $code, $limit = 8, $languageId = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $collection = new bannerCollection();
        $collection->where(['banner.code' => $code, 'banner.status' => 1]);
        if ($languageId != '') {
            $collection->where(['banner_language.language_id' => $languageId]);
        } else {
            $collection->where(['banner_language.language_id' => Bootstrap::getLanguage()->getId()]);
        }
        //echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        $collection->load(true, true);
        if (count($collection) > 0) {
            for ($i = 0; $i < count($collection); $i++) {
                $collection[$i]['image'] = $this->getResourceUrl('image/' . $collection[$i]['image']);
            }
        }
        if (count($collection) > 0) {
            $this->responseData = ['statusCode' => '200', 'data' => $collection->toArray(), 'message' => 'get banner successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '404', 'data' => [], 'message' => 'not fount the banner with code:' . $code];
            return $this->responseData;
        }
    }
}
