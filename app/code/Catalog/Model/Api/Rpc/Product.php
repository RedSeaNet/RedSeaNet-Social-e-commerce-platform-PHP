<?php

namespace Redseanet\Catalog\Model\Api\Rpc;

use DOMDocument;
use DOMXPath;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Catalog\Model\Product as Model;
use Redseanet\Search\Model\Factory;
use Laminas\Db\Sql\Predicate\In;
use Redseanet\Catalog\Model\Collection\SearchTerm as Terms;
use Redseanet\Catalog\Model\SearchTerm;
use Redseanet\Catalog\Model\Collection\Category as Categories;
use Redseanet\Catalog\Model\Collection\Product\Option as OptionCollection;
use Redseanet\Catalog\Model\Collection\Warehouse as WarehouseCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\I18n\Model\Currency;
use Redseanet\Retailer\Model\Collection\Manager as retailerManager;

class Product extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;

    public function getProductById($id, $token, $pid, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $product = new Model($languageId);
        //$product->setLanguageId($languageId);
        $product->load(intval($pid));
        $options = [];
        $optionsCollection = new OptionCollection();
        $optionsCollection->withLabel($languageId)
                ->where(['product_id' => $pid])
                ->order('sort_order ASC');
        foreach ($optionsCollection as $option) {
            $tmpOption = [];
            $tmpOption['input'] = $option->offsetGet('input');
            $tmpOption['selected'] = false;
            $tmpOption['id'] = $option->getId();
            $tmpOption['title'] = ($option->offsetGet('title') != '' ? $option->offsetGet('title') : $option->offsetGet('default_title'));
            $tmpOption['value'] = [];
            $values = $option->offsetGet('value');
            for ($i = 0; $i < count($values); $i++) {
                $values[$i]['selected'] = false;
                if ($values[$i]['title'] != '') {
                    $tmpOption['value'][] = $values[$i];
                } else {
                    $values[$i]['title'] = $values[$i]['default_title'];
                    $tmpOption['value'][] = $values[$i];
                }
            }
            $options[] = $tmpOption;
        }
        $productArray = $product->toArray();
        $currency = new Currency();
        $currency->load($currencyCode, 'code');

        $productArray['price'] = $currency->convert($productArray['price']);
        $productArray['msrp'] = $currency->convert($productArray['msrp']);
        $bulk_price = json_decode($productArray['bulk_price'], true);
        $new_bulk_price = [];
        if (is_array($bulk_price) && count($bulk_price) > 0) {
            foreach ($bulk_price as $bulk_key => $bulk_value) {
                $new_bulk_price[$bulk_key] = $currency->convert($bulk_value);
            }
        }
        $productArray['bulk_price'] = $new_bulk_price;
        $doc = new DOMDocument();
        $doc->loadHTML($productArray['description']);
        $xpath = new DOMXPath($doc);
        $nodelist = $xpath->query('//img');
        $productArray['descriptionimages'] = [];
        if ($nodelist->count() > 0) {
            for ($i = 0; $i < $nodelist->count(); $i++) {
                $node = $nodelist->item($i);
                $value = $node->attributes->getNamedItem('src')->nodeValue;
                $productArray['descriptionimages'][] = $value;
            }
        }
        $resultData = $productArray;
        $resultData['options'] = $options;
        $retailermanager = new retailerManager();
        $retailermanager->join('retailer', 'retailer.id=retailer_manager.retailer_id', [], 'left');
        $retailermanager->where(['retailer.store_id' => $resultData['store_id']]);
        $resultData['retailer_manager'] = [];
        if (count($retailermanager) > 0) {
            $resultData['retailer_manager'] = $retailermanager[0]->toArray();
        }
        $resultData["in_stock"] = 2;
        $resultData["sold"] = 5;
        if (!empty($resultData["additional"])) {
            $resultData["additional"] = json_decode($resultData["additional"], true);
        } else {
            $resultData["additional"] = [];
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get product data successfully'];
        return $this->responseData;
    }

    public function getProductByKeyword($id, $token, $conditionData = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $resultData = [];
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $products = new Collection($languageId);
        if (isset($conditionData['status'])) {
            $products->where(['main_table.status' => intval($conditionData['status'])]);
        }
        if (!empty($conditionData['q'])) {
            $engine = (new Factory())->getSearchEngineHandler();
            $result = $engine->select('catalog_search', $conditionData, $languageId);
            $ids = [];
            foreach ($result as $item) {
                $ids[$item['id']] = $item['weight'] ?? 0;
            }
            if (count($ids)) {
                $products->where(new In('id', array_keys($ids)));
                $resultData['total'] = count($ids);
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
        $total = $products->count();
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
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $tmpResultData = [];
        if ($conditionData['page'] > 1) {
            $products->order('id DESC')->limit($conditionData['limit'])->offset((int) ($conditionData['page'] - 1) * $conditionData['limit']);
        } else {
            $products->order('id DESC')->limit($conditionData['limit'])->offset(0);
        }
        $tmpResultData_for = $products->load(true)->toArray();
        if (count($tmpResultData_for) > 0) {
            for ($i = 0; count($tmpResultData_for) > $i; $i++) {
                $imageReturnData = [];
                if ($tmpResultData_for[$i]['images'] != '') {
                    $imagesArray = json_decode($tmpResultData_for[$i]['images'], true);
                    if (count($imagesArray) > 0) {
                        for ($ii = 0; count($imagesArray) > $ii; $ii++) {
                            if (isset($imagesArray[$ii]['name']) && $imagesArray[$ii]['name'] != '') {
                                $imageReturnData[] = ['label' => $imagesArray[$ii]['label'], 'id' => $imagesArray[$ii]['id'], 'name' => $imagesArray[$ii]['name'], 'group' => $imagesArray[$ii]['group'], 'src' => $this->getResourceUrl('image/' . $imagesArray[$ii]['name'])];
                            }
                        }
                    }
                }
                unset($tmpResultData_for[$i]['images']);
                $tmpResultData_for[$i]['images'] = $imageReturnData;
                $tmpResultData_for[$i]['price'] = $currency->convert($tmpResultData_for[$i]['price']);
                $tmpResultData_for[$i]['msrp'] = $currency->convert($tmpResultData_for[$i]['msrp']);
                if (!empty($tmpResultData_for[$i]['thumbnail'])) {
                    $tmpResultData_for[$i]['thumbnailuri'] = $tmpResultData_for[$i]->getThumbnail();
                } else {
                    $tmpResultData_for[$i]['thumbnailuri'] = $this->getPubUrl('frontend/images/placeholder.png');
                }
                $tmpResultData[] = $tmpResultData_for[$i]->toArray();
            }
        }
        $resultData['products'] = $tmpResultData;
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get product data successfully'];
        return $this->responseData;
    }

    public function getProductByCategoryIds($id, $token, $conditionData = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $resultData = [];
        $products = new Collection($languageId);
        if (isset($conditionData['categories']) && is_array($conditionData['categories']) && count($conditionData['categories']) > 0) {
            $products->join('product_in_category', 'id=product_in_category.product_id', [], 'left');
            $products->where(new In('product_in_category.category_id', $conditionData['categories']));
        }
        if (isset($conditionData['status'])) {
            $products->where(['main_table.status' => intval($conditionData['status'])]);
        }
        if (!isset($conditionData['limit']) || $conditionData['limit'] == '') {
            $conditionData['limit'] = 20;
        } else {
            $conditionData['limit'] = intval($conditionData['limit']);
        }
        if (!isset($conditionData['page']) || $conditionData['page'] == '') {
            $conditionData['page'] = 0;
        } else {
            $conditionData['page'] = intval($conditionData['page']);
        }
        if ($conditionData['page'] > 0) {
            $products->order('main_table.id DESC')->limit($conditionData['limit'])->offset((int) ($conditionData['page'] - 1) * $conditionData['limit']);
        } else {
            $products->order('main_table.id DESC')->limit($conditionData['limit'])->offset(0);
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $tmpResultData = [];
        $tmpResultData_for = $products->load(true)->toArray();
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
                } else {
                    $imageReturnData[] = ['label' => '', 'id' => '', 'name' => '', 'group' => '', 'src' => $this->getPubUrl('frontend/images/placeholder.png')];
                }
                unset($tmpResultData_for[$i]['images']);
                $tmpResultData_for[$i]['images'] = $imageReturnData;
                $tmpResultData_for[$i]['price'] = $currency->convert($tmpResultData_for[$i]['price']);
                $tmpResultData_for[$i]['msrp'] = $currency->convert($tmpResultData_for[$i]['msrp']);
                $tmpResultData[] = $tmpResultData_for[$i]->toArray();
            }
        }
        $resultData['products'] = $tmpResultData;

        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get product data successfully by category ids'];
        return $this->responseData;
    }

    public function getProductByCategoryId($id, $token, $categoryId, $conditionData = [], $withCategoryInfo = false, $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $resultData = [];
        $products = new Collection($languageId);

        $products->join('product_in_category', 'id=product_in_category.product_id', [], 'left');
        $products->where(['product_in_category.category_id' => intval($categoryId)]);

        if (isset($conditionData['status'])) {
            $products->where(['main_table.status' => intval($conditionData['status'])]);
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
        $total = $products->count();
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
            $products->order('main_table.id DESC')->limit($conditionData['limit'])->offset((int) ($conditionData['page'] - 1) * $conditionData['limit']);
        } else {
            $products->order('main_table.id DESC')->limit($conditionData['limit'])->offset(0);
        }
        //echo $products->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $tmpResultData = [];

        $tmpResultData_for = $products->load(true)->toArray();
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
                    } else {
                        $imageReturnData[] = ['label' => '', 'id' => '', 'name' => '', 'group' => '', 'src' => $this->getPubUrl('frontend/images/placeholder.png')];
                    }
                } else {
                    $imageReturnData[] = ['label' => '', 'id' => '', 'name' => '', 'group' => '', 'src' => $this->getPubUrl('frontend/images/placeholder.png')];
                }
                unset($tmpResultData_for[$i]['images']);
                $tmpResultData_for[$i]['images'] = $imageReturnData;
                $tmpResultData_for[$i]['price'] = $currency->convert($tmpResultData_for[$i]['price']);
                $tmpResultData_for[$i]['msrp'] = $currency->convert($tmpResultData_for[$i]['msrp']);
                $tmpResultData[] = $tmpResultData_for[$i]->toArray();
            }
        }
        $resultData['products'] = $tmpResultData;
        if ($withCategoryInfo) {
            $category = new Categories($languageId);
            $category->where(['id' => $categoryId]);
            if (count($category) > 0) {
                $resultData['category'] = $category[0]->toArray();
                if (!empty($category[0]['thumbnail'])) {
                    $resultData['category']['thumbnailuri'] = $category[0]->getThumbnail();
                } else {
                    $resultData['category']['thumbnailuri'] = $this->getPubUrl('frontend/images/placeholder.png');
                }
                $childrenCategories = new Categories($languageId);
                $childrenCategories->where(['parent_id' => $categoryId]);
                $resultData['category']['children'] = [];
                if (count($childrenCategories) > 0) {
                    foreach ($childrenCategories as $child) {
                        if (!empty($child['thumbnail'])) {
                            $child['thumbnailuri'] = $child->getThumbnail();
                        } else {
                            $child['thumbnailuri'] = $this->getPubUrl('frontend/images/placeholder.png');
                        }
                        $resultData['category']['children'][] = $child->toArray();
                    }
                }
            } else {
                $resultData['category'] = [];
            }
        } else {
            $resultData['category'] = [];
        }

        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get product data successfully by category id'];
        return $this->responseData;
    }

    public function deleteProduct() {
        $attributes = $this->getAttributes(Model::ENTITY_TYPE, false);
        if ($this->authOptions['validation'] === -1 && count($attributes)) {
            $id = $this->getRequest()->getQuery('id');
            if ($id) {
                $product = new Model();
                $product->setId($id)->remove();
                return $this->getResponse()->withStatus(202);
            }
            return $this->getResponse()->withStatus(400);
        }
        return $this->getResponse()->withStatus(403);
    }

    public function putProduct() {
        $attributes = $this->getAttributes(Model::ENTITY_TYPE, false);
        if ($this->authOptions['validation'] === -1 && count($attributes)) {
            $id = $this->getRequest()->getQuery('id');
            $product = new Model();
            if ($id) {
                $product->load($id);
            }
            $data = $this->getRequest()->getPost();
            $set = [];
            foreach ($attributes as $attribute) {
                if (isset($data[$attribute])) {
                    $set[$attribute] = $data[$attribute];
                }
            }
            if ($set) {
                $product->setData($set);
                $product->save();
            }
            return $this->getResponse()->withStatus(202);
        }
        return $this->getResponse()->withStatus(403);
    }

    public function getProductLink($id, $token, $productId, $linkType = "r", $conditionData = [], $languageId = 0, $currencyCode = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $resultData = [];
        $products = new Collection($languageId);

        $products->join('product_link', 'id=product_link.linked_product_id', [], 'left');
        $products->where(['product_link.type' => $linkType, 'product_link.product_id' => $productId]);
        if (isset($conditionData['status'])) {
            $products->where(['main_table.status' => intval($conditionData['status'])]);
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
        $total = $products->count();
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
            $products->order('main_table.id DESC')->limit($conditionData['limit'])->offset((int) ($conditionData['page'] - 1) * $conditionData['limit']);
        } else {
            $products->order('main_table.id DESC')->limit($conditionData['limit'])->offset(0);
        }
        //echo $products->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $tmpResultData = [];

        $tmpResultData_for = $products->load(true)->toArray();
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
                $tmpResultData_for[$i]['price'] = $currency->convert($tmpResultData_for[$i]['price']);
                $tmpResultData_for[$i]['msrp'] = $currency->convert($tmpResultData_for[$i]['msrp']);
                $tmpResultData[] = $tmpResultData_for[$i]->toArray();
            }
        }
        $resultData['products'] = $tmpResultData;
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get product data successfully by category id'];
        return $this->responseData;
    }

}
