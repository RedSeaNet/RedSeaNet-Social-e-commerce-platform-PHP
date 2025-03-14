<?php

namespace Redseanet\Catalog\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Catalog\Model\Category as Model;
use Redseanet\Catalog\Model\Collection\Category as Collection;
use Redseanet\Lib\Model\Collection\Eav\Attribute\Set;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Bootstrap;
use Laminas\Math\Rand;
use Redseanet\Resource\Model\Resource;
use Redseanet\Lib\Tool\PHPTree;

class Category extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;

    public function getCategory($id, $token, $languageId = 0, $conditionData = []) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $categories = new Collection($languageId);
        //$categories->columns(["id","store_id","attribute_set_id","status","parent_id","sort_order","block","description","display_mode","image","include_in_menu","meta_description","meta_keywords","meta_title","name","thumbnail","uri_key","sortable",(new Laminas_Db_Expr($resourceCollection->where(["id"=>"image"]))) => "imageName"]);
        $attributes = new Attribute();
        $attributes->withSet()->where([
                    'searchable' => 1
                ])->columns(['code'])
                ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id AND eav_entity_type.id=eav_attribute_set.type_id', [], 'right')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
        $searchable = [];
        $attributes->walk(function ($attribute) use (&$searchable) {
            $searchable[] = $attribute['code'];
        });
        if (count($conditionData) > 0) {
            foreach ($conditionData as $conditionDataK => $conditionDataV) {
                if (in_array($conditionDataK, $searchable)) {
                    $categories->where([$conditionDataK => $conditionDataV]);
                }
            }
        }
        $categories->load(true, true);
        $tmpCategotyData = [];
        $categories->toArray();
        if (count($categories) > 0) {
            foreach ($categories as $key => $value) {
                if (!empty($value['image'])) {
                    $resourceImage = new Resource();
                    $resourceImage->load($value['image']);
                    if (!empty($resourceImage->offsetGet('real_name'))) {
                        $value['imagename'] = $resourceImage->offsetGet('real_name');
                        $value['imageuri'] = $this->getResourceUrl('image/' . $resourceImage->offsetGet('real_name'));
                    } else {
                        $value['imagename'] = '';
                        $value['imageuri'] = '';
                    }
                } else {
                    $value['imagename'] = '';
                    $value['imageuri'] = '';
                }

                if ($value['thumbnail'] != '') {
                    $resourceThumbnail = new Resource();
                    $resourceThumbnail->load($value['thumbnail']);
                    if ($resourceThumbnail->offsetGet('real_name') != '') {
                        $value['thumbnailname'] = $resourceThumbnail->offsetGet('real_name');
                        $value['thumbnailuri'] = $this->getResourceUrl('image/' . $resourceThumbnail->offsetGet('real_name'));
                    } else {
                        $value['thumbnailname'] = '';
                        $value['thumbnailuri'] = '';
                    }
                } else {
                    $value['thumbnailname'] = '';
                    $value['thumbnailuri'] = '';
                }

                $tmpCategotyData[] = $value;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $tmpCategotyData, 'message' => 'get category data successfully'];
        return $this->responseData;
    }

    public function deleteCategory($id, $token, $cid) {
        $this->validateToken($id, $token, __FUNCTION__, true);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($cid) {
            $category = new Model();
            $category->setId($cid)->remove();
            $this->responseData = ['statusCode' => '', 'data' => ['id' => $cid], 'message' => 'to delete the category successfully'];
            return $this->responseData;
        }
        $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'The category is public, so it cannot be to delete'];
        return $this->responseData;
    }

    public function putCategory($id, $token, $data) {
        $this->validateToken($id, $token, __FUNCTION__, true);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $attributes = new Attribute();
        $attributes->withSet()->where([
                    'is_required' => 1
                ])->columns(['code'])
                ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id AND eav_entity_type.id=eav_attribute_set.type_id', [], 'right')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
        $required = ['store_id'];
        if (isset($data['attribute_set_id']) && $data['attribute_set_id'] != '') {
            $setId = intval($data['attribute_set_id']);
        } else {
            $setId = 0;
        }
        $attributes->walk(function ($attribute) use (&$required, &$setId) {
            $required[] = $attribute['code'];
            if (!$setId) {
                $setId = $attribute['attribute_set_id'];
            }
        });
        foreach ($required as $code) {
            if (!isset($data[$code]) || $data[$code] === '') {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'The field ' . $code . ' is required'];
                return $this->responseData;
            }
        }
        $model = new Model((isset($data['language_id']) && $data['language_id'] != '') ? intval($data['language_id']) : Bootstrap::getLanguage()->getId(), $data);
        if (empty($data['id'])) {
            $model->setId(null);
        }
        $type = new Type();
        $type->load(Model::ENTITY_TYPE, 'code');
        $model->setData([
            'type_id' => $type->getId(),
            'attribute_set_id' => $setId
        ]);
        $tmpkey = Rand::getString(200);
        if (empty($data['parent_id'])) {
            $model->setData('parent_id', null);
        } elseif (empty($data['uri_key'])) {
            $model->setData('uri_key', $tmpkey);
        }
        if (empty($data['thumbnail'])) {
            $model->setData('thumbnail', null);
        }
        if (empty($data['image'])) {
            $model->setData('image', null);
        }
        try {
            $this->beginTransaction();
            $model->save();
            if ($model->offsetGet('uri_key') === $tmpkey) {
                $model->setData('uri_key', 'c-' . $model->getId())->save();
            }
            $languages = new Language();
            $languages->columns(['id']);
            $languages->load(true, false);
            foreach ($languages as $language) {
                $this->reindex($model->getId(), $language['id']);
            }
            $this->commit();
            $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'add the category successfully'];
            return $this->responseData;
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '503', 'data' => [], 'message' => 'add the category failly' + $e];
            return $this->responseData;
        }
    }

    private function reindex($id, $languageId) {
        $model = new Model($languageId);
        $model->load($id);
        $tmp = $model;
        $path = [$model['uri_key']];
        while (($tmp = $tmp->getParentCategory()) && $tmp['uri_key']) {
            array_unshift($path, $tmp['uri_key']);
        }
        $path = implode('/', $path);
        $values = [['product_id' => null, 'category_id' => $id, 'path' => $path]];
        foreach ($model->getProducts() as $product) {
            $values[] = ['product_id' => $product['id'], 'category_id' => $id, 'path' => $path . '/' . $product['uri_key']];
        }
        $this->getContainer()->get('indexer')->replace('catalog_url', $languageId, $values, ['category_id' => $id]);
        foreach ($model->getChildrenCategories() as $child) {
            $this->reindex($child['id'], $languageId);
        }
    }

    public function getCategoryTreeByParentId($id, $token, $parentId, $languageId = 0, $conditionData = []) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $categories = new Collection($languageId);
        $categories->where(["parent_id", $parentId]);
        //$categories->columns(["id","store_id","attribute_set_id","status","parent_id","sort_order","block","description","display_mode","image","include_in_menu","meta_description","meta_keywords","meta_title","name","thumbnail","uri_key","sortable",(new Laminas_Db_Expr($resourceCollection->where(["id"=>"image"]))) => "imageName"]);
        $attributes = new Attribute();
        $attributes->withSet()->where([
                    'searchable' => 1
                ])->columns(['code'])
                ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id AND eav_entity_type.id=eav_attribute_set.type_id', [], 'right')
                ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
        $searchable = [];
        $attributes->walk(function ($attribute) use (&$searchable) {
            $searchable[] = $attribute['code'];
        });
        if (count($conditionData) > 0) {
            foreach ($conditionData as $conditionDataK => $conditionDataV) {
                if (in_array($conditionDataK, $searchable)) {
                    $categories->where([$conditionDataK => $conditionDataV]);
                }
            }
        }
        //echo $categories->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $categories->load(true, true);
        $tmpCategotyData = [];
        $model = new Model($languageId);
        $model->load($parentId);
        $modealArray = $model->toArray();
        if (!empty($modealArray['image'])) {
            $resourceImage = new Resource();
            $resourceImage->load($modealArray['image']);
            if (!empty($resourceImage->offsetGet('real_name'))) {
                $modealArray['imagename'] = $resourceImage->offsetGet('real_name');
                $modealArray['imageuri'] = $this->getResourceUrl('image/' . $resourceImage->offsetGet('real_name'));
            } else {
                $modealArray['imagename'] = '';
                $modealArray['imageuri'] = '';
            }
        } else {
            $modealArray['imagename'] = '';
            $modealArray['imageuri'] = '';
        }

        if ($modealArray['thumbnail'] != '') {
            $resourceThumbnail = new Resource();
            $resourceThumbnail->load($modealArray['thumbnail']);
            if ($resourceThumbnail->offsetGet('real_name') != '') {
                $modealArray['thumbnailname'] = $resourceThumbnail->offsetGet('real_name');
                $modealArray['thumbnailuri'] = $this->getResourceUrl('image/' . $resourceThumbnail->offsetGet('real_name'));
            } else {
                $modealArray['thumbnailname'] = '';
                $modealArray['thumbnailuri'] = '';
            }
        } else {
            $modealArray['thumbnailname'] = '';
            $modealArray['thumbnailuri'] = '';
        }
        $modealArray["parent_id"]=0;
        $tmpCategotyData[] = $modealArray;
        $categories->toArray();
        if (count($categories) > 0) {
            foreach ($categories as $key => $value) {
                if (!empty($value['image'])) {
                    $resourceImage = new Resource();
                    $resourceImage->load($value['image']);
                    if (!empty($resourceImage->offsetGet('real_name'))) {
                        $value['imagename'] = $resourceImage->offsetGet('real_name');
                        $value['imageuri'] = $this->getResourceUrl('image/' . $resourceImage->offsetGet('real_name'));
                    } else {
                        $value['imagename'] = '';
                        $value['imageuri'] = '';
                    }
                } else {
                    $value['imagename'] = '';
                    $value['imageuri'] = '';
                }

                if ($value['thumbnail'] != '') {
                    $resourceThumbnail = new Resource();
                    $resourceThumbnail->load($value['thumbnail']);
                    if ($resourceThumbnail->offsetGet('real_name') != '') {
                        $value['thumbnailname'] = $resourceThumbnail->offsetGet('real_name');
                        $value['thumbnailuri'] = $this->getResourceUrl('image/' . $resourceThumbnail->offsetGet('real_name'));
                    } else {
                        $value['thumbnailname'] = '';
                        $value['thumbnailuri'] = $this->getPubUrl('frontend/images/placeholder.png');
                    }
                } else {
                    $value['thumbnailname'] = '';
                    $value['thumbnailuri'] = $this->getPubUrl('frontend/images/placeholder.png');
                }
                $tmpCategotyData[] = $value;
            }
        }
        $treeData=PHPTree::makeTree($tmpCategotyData);
        $this->responseData = ['statusCode' => '200', 'data' => $treeData[0], 'message' => 'get category data successfully'];
        return $this->responseData;
    }

}
