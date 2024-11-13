<?php

namespace Redseanet\Log\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Lib\Bootstrap;
use Redseanet\Customer\Model\Customer;
use Redseanet\Catalog\Model\Collection\Product;

class Visitor extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Url;

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getProductsVisitHistoryList($id, $token, $customerId, $languageId = 0, $page = 1, $limit = 20)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $products = new Product($languageId);
        $products->join('log_visitor', 'log_visitor.product_id=main_table.id', ['viewed_at' => 'created_at'], 'left')->order('log_visitor.created_at DESC')
                ->where([
                    'log_visitor.customer_id' => intval($customerId)
                ])->where->isNotNull('product_id');
        $products->offset(($page > 0 ? ($page - 1) : 0) * $limit)->limit((int) $limit);
        //echo $products->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $products->load(true, true);
        $tmpResultData_for = $products->load(true, true);
        $resultData = [];
        if (count($tmpResultData_for) > 0) {
            for ($i = 0; count($tmpResultData_for) > $i; $i++) {
                $imageReturnData = [];
                if ($tmpResultData_for[$i]['images'] != '') {
                    //var_dump($tmpResultData_for[$i]["images"]);exit('---');
                    $imagesArray = json_decode($tmpResultData_for[$i]['images'], true);
                    if (count($imagesArray) > 0) {
                        for ($ii = 0; count($imagesArray) > $ii; $ii++) {
                            if ($imagesArray[$ii]['name'] != '') {
                                $imageReturnData[] = ['label' => $imagesArray[$ii]['label'], 'id' => $imagesArray[$ii]['id'], 'name' => $imagesArray[$ii]['name'], 'group' => $imagesArray[$ii]['group'], 'src' => $this->getResourceUrl('image/' . $imagesArray[$ii]['name'])];
                            }
                        }
                    }
                }
                unset($tmpResultData_for[$i]['images']);
                $tmpResultData_for[$i]['images'] = $imageReturnData;
                $resultData[] = $tmpResultData_for[$i];
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'products visited history list successfully'];
        return $this->responseData;
    }
}
