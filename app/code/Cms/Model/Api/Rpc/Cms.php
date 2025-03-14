<?php

namespace Redseanet\Cms\Model\Api\Rpc;

use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Lib\Bootstrap;
use Redseanet\Cms\Model\Category;
use Redseanet\Cms\Model\Page as Model;
use Redseanet\Cms\Model\Collection\Category as CategoryCollection;
use Redseanet\Cms\Model\Collection\Page as PageCollection;

class Cms extends AbstractHandler
{
    /**
     * @param string $id
     * @param string $token
     * @param int $uriKey
     * @param int $languageId
     * @return array
     */
    public function getPageByUrikey($id, $token, $uriKey, $languageId = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $collection = new PageCollection();
        $collection->join('cms_page_language', 'id=cms_page_language.page_id', ['language_id'], 'left');
        $collection->where(['uri_key' => $uriKey]);
        if ($languageId != '') {
            $collection->where(['cms_page_language.language_id' => $languageId]);
        } else {
            $collection->where(['cms_page_language.language_id' => Bootstrap::getLanguage()->getId()]);
        }
        $collection->load(true, true);
        if (count($collection) > 0) {
             $pageData=$collection[0];
            $this->responseData = ['statusCode' => '200', 'data' => $pageData, 'message' => 'get page successfully'];
            //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($collection->toArray())));
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '404', 'data' => [], 'message' => 'not fount the page with key:' . $uriKey];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $pid
     * @param int $languageId
     * @return array
     */
    public function getPageById($id, $token, $pid, $languageId = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $collection = new PageCollection();
        $collection->join('cms_page_language', 'id=cms_page_language.page_id', ['language_id'], 'left');
        $collection->where(['id' => $pid]);
        if ($languageId != '') {
            $collection->where(['cms_page_language.language_id' => $languageId]);
        } else {
            $collection->where(['cms_page_language.language_id' => Bootstrap::getLanguage()->getId()]);
        }
        $collection->load(true, true);
        if (count($collection) > 0) {
            $pageData=$collection[0];
            $this->responseData = ['statusCode' => '200', 'data' => $pageData, 'message' => 'get page successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '404', 'data' => [], 'message' => 'not fount the page with page id:' . $pid];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $categoryId
     * @param int $languageId
     * @return array
     */
    public function getPageListByCategoryId($id, $token, $categoryId, $languageId = '', $page = 0, $limit = 20)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $collection = new PageCollection();
        $collection->join('cms_page_language', 'id=cms_page_language.page_id', ['language_id'], 'left');
        $collection->join('cms_category_page', 'cms_page_language.page_id=cms_category_page.page_id', ['category_id'], 'left');
        $collection->columns(['id', 'status', 'uri_key', 'title', 'keywords', 'description', 'thumbnail', 'image']);
        $collection->where(['cms_category_page.category_id' => $categoryId]);
        if ($languageId != '') {
            $collection->where(['cms_page_language.language_id' => $languageId]);
        } else {
            $collection->where(['cms_page_language.language_id' => Bootstrap::getLanguage()->getId()]);
        }
        $collection->offset(($page > 0 ? ($page - 1) : 0) * $limit)->limit((int) $limit);
        $collection->order('cms_page.updated_at DESC,cms_page.created_at DESC');
        $collection->load(true, true);
        $category = new Category();
        $category->load(intval($categoryId));
        $resultData = $category->toArray();
        $resultData['pages'] = $collection;
        if (count($collection) > 0) {
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get cart page successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '404', 'data' => [], 'message' => 'not fount the page with category id:' . $categoryId];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param istring $categoryUrikey
     * @param int $languageId
     * @return array
     */
    public function getPageListByCategoryUrikey($id, $token, $categoryUrikey, $languageId = '', $page = 0, $limit = 20)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $category = new CategoryCollection();
        $category->where(['uri_key' => $categoryUrikey]);
        $categoryData = $category->load(true, true);
        if (count($categoryData) > 0) {
            $resultData = $categoryData[0];
            $collection = new PageCollection();
            $collection->join('cms_page_language', 'id=cms_page_language.page_id', ['language_id'], 'left');
            $collection->join('cms_category_page', 'cms_page_language.page_id=cms_category_page.page_id', ['category_id'], 'left');
            $collection->columns(['id', 'status', 'uri_key', 'title', 'keywords', 'description', 'thumbnail', 'image']);
            $collection->where(['cms_category_page.category_id' => $resultData['id']]);

            if ($languageId != '') {
                $collection->where(['cms_page_language.language_id' => $languageId]);
            } else {
                $collection->where(['cms_page_language.language_id' => Bootstrap::getLanguage()->getId()]);
            }
            $collection->where(['cms_page.status' => 1]);
            $collection->offset(($page > 0 ? ($page - 1) : 0) * $limit)->limit((int) $limit);
            $collection->order('cms_page.updated_at DESC,cms_page.created_at DESC');
            $collection->load(true, true);
            $resultData['pages'] = [];
            for ($p = 0; $p < count($collection); $p++) {
                $resultData['pages'][] = $collection[$p];
            }
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get cms page list successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '404', 'data' => [], 'message' => 'not fount the page with category urikey:' . $categoryUrikey];
            return $this->responseData;
        }
    }
}
