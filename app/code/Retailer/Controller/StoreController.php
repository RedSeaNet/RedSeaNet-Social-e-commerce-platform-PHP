<?php

namespace Redseanet\Retailer\Controller;

use Exception;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Retailer\Model\StoreTemplate;
use Redseanet\Retailer\Model\StorePicInfo;
use Redseanet\Retailer\Model\Collection\StoreTemplateCollection;
use Redseanet\Resource\Model\Collection\Category;
use Redseanet\Customer\Model\Customer;
use Redseanet\Resource\Model\Resource;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\ViewModel\StoreDecoration;
use Redseanet\Resource\Lib\Factory as resourceFactory;

/**
 * Retailer submenu store management controller
 *
 */
class StoreController extends AuthActionController
{
    private $page_types = [
        '首页' => 0,
        '产品详情页' => 2
    ];

    public function settingAction()
    {
        return $this->getLayout('retailer_store_setting');
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['store', 'uri_key']);
            if ($result['error'] === 0) {
                try {
                    $segment = new Segment('customer');
                    $customerId = $segment->get('customer')['id'];
                    $retailer = new Retailer();
                    $retailer->load($customerId, 'customer_id');
                    $store = $retailer->getStore();
                    if ($store['name'] !== $data['store']['name']) {
                        $store->setData('name', $data['store']['name'])->save();
                    }
                    unset($data['store_id'], $data['customer_id']);

                    if (isset($data['profile']) && !empty($data['profile']) && preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['profile'], $fileResult)) {
                        $type = $fileResult[2];
                        if (!is_dir(BP . 'pub/upload/store/')) {
                            mkdir(BP . 'pub/upload/store/', 0777, true);
                        }
                        if (!is_dir(BP . 'pub/upload/store/' . $retailer['store_id'] . '/')) {
                            mkdir(BP . 'pub/upload/store/' . $retailer['store_id'] . '/', 0777, true);
                        }
                        $name = 'retailer-profile-' . $retailer['store_id'] . '-' . date('YmdHis') . '.' . $type;
                        $path = BP . 'pub/upload/store/' . $retailer['store_id'] . '/' . $name;
                        if (file_put_contents($path, base64_decode(str_replace($fileResult[1], '', $data['profile'])))) {
                            $data['profile'] = $name;
                            resourceFactory::uploadFileToClound($path, 'pub/upload/store/' . $retailer['store_id'] . '/' . $name, $name);
                        } else {
                            $data['profile'] = $retailer['profile'];
                        }
                    } else {
                        $data['profile'] = $retailer['profile'];
                    }
                    if (isset($data['watermark']) && !empty($data['watermark']) && preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['watermark'], $fileResult)) {
                        $type = $fileResult[2];
                        if (!is_dir(BP . 'pub/upload/store/')) {
                            mkdir(BP . 'pub/upload/store/', 0777, true);
                        }
                        if (!is_dir(BP . 'pub/upload/store/' . $retailer['store_id'] . '/')) {
                            mkdir(BP . 'pub/upload/store/' . $retailer['store_id'] . '/', 0777, true);
                        }
                        $name = 'retailer-watermark-' . $retailer['store_id'] . '-' . date('YmdHis') . '.' . $type;
                        $path = BP . 'pub/upload/store/' . $retailer['store_id'] . '/' . $name;
                        if (file_put_contents($path, base64_decode(str_replace($fileResult[1], '', $data['watermark'])))) {
                            $data['watermark'] = $name;
                            resourceFactory::uploadFileToClound($path, 'pub/upload/store/' . $retailer['store_id'] . '/' . $name, $name);
                        } else {
                            $data['watermark'] = $retailer['watermark'];
                        }
                    } else {
                        $data['watermark'] = $retailer['watermark'];
                    }

                    if (isset($data['contact']['wechat']) && !empty($data['contact']['wechat']) && preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['contact']['wechat'], $fileResult)) {
                        $type = $fileResult[2];
                        if (!is_dir(BP . 'pub/upload/store/')) {
                            mkdir(BP . 'pub/upload/store/', 0777, true);
                        }
                        if (!is_dir(BP . 'pub/upload/store/' . $retailer['store_id'] . '/')) {
                            mkdir(BP . 'pub/upload/store/' . $retailer['store_id'] . '/', 0777, true);
                        }
                        $name = 'retailer-wechat-' . $retailer['store_id'] . '-' . date('YmdHis') . '.' . $type;
                        $path = BP . 'pub/upload/store/' . $retailer['store_id'] . '/' . $name;
                        if (file_put_contents($path, base64_decode(str_replace($fileResult[1], '', $data['contact']['wechat'])))) {
                            $data['contact']['wechat'] = $name;
                            resourceFactory::uploadFileToClound($path, 'pub/upload/store/' . $retailer['store_id'] . '/' . $name, $name);
                        }
                        unset($data['wechat']);
                    }
                    $data['contact'] = json_encode($data['contact']);
                    $retailer->setData($data)->save();
                    $result['message'][] = ['message' => $this->translate('Store infomation has been updated successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/store/setting/', 'retailer');
    }

    public function brandAction()
    {
        $root = $this->getLayout('retailer_brand');
        return $root;
    }

    public function viewAction()
    {
        $root = $this->getLayout('retailer_store_view');
        return $root;
    }

    public function viewSearchAction()
    {
        $root = $this->getLayout('view_search');
        return $root;
    }

    public function viewCustomPageAction()
    {
        $id = $this->getRequest()->getQuery('id');
        $storetemplate = new StoreTemplate();
        $storetemplate->load(intval($id));
        if (empty($storetemplate['store_id'])) {
            return $this->notFoundAction();
        }
        $root = $this->getLayout('retailer_store_custom_view');
        return $root;
    }

    public function viewProductAction()
    {
        $id = $this->getRequest()->getQuery('id');
        $product = new Product();
        $product->load(intval($id));
        $root = $this->getLayout('retailer_store_product_view');
        $main = $root->getChild('main', true);
        $main->setVariable('store_id', $product['store_id']);
        //$main->getChild('product', true)->setVariable('product', $product);
        $main->getChild('product', true)->setProduct($product);
        return $root;
    }

    /**
     * decorationAction
     * decorate store page
     * @access public
     * @return object
     */
    public function decorationAction()
    {
        $root = $this->getLayout('decoration_store');
        $root->getChild('main', true)->setVariable('page_types', $this->page_types);
        return $root;
    }

    public function decorationCustomizeAction()
    {
        $template_name = $this->getRequest()->getQuery('template_name');
        $id = $this->getRequest()->getQuery('id');
        $template_id = $this->getRequest()->getQuery('template_id');
        $root = $this->getLayout('decoration_store_customize');
        $root->getChild('main', true)->setVariable('template_name', $template_name);
        $root->getChild('main', true)->setVariable('template_id', $template_id);
        $root->getChild('main', true)->setVariable('id', $id);
        $root->getChild('main', true)->setVariable('page_types', $this->page_types);
        return $root;
    }

    public function decorationProductDetailAction()
    {
        $template_name = $this->getRequest()->getQuery('template_name');
        $StoreDecoration = new StoreDecoration();
        $template_id = $this->getRequest()->getQuery('template_id');
        $id = $StoreDecoration->getProductDetailPageID($template_id);
        $root = $this->getLayout('decoration_store_productdetail');
        $root->getChild('main', true)->setVariable('template_name', $template_name);
        $root->getChild('main', true)->setVariable('template_id', $template_id);
        $root->getChild('main', true)->setVariable('id', $id);
        $root->getChild('main', true)->setVariable('page_types', $this->page_types);
        return $root;
    }

    public function decorationListAction()
    {
        $root = $this->getLayout('decoration_list');
        $root->getChild('main', true)->setVariable('subtitle', 'Sales of Goods');
        return $root;
    }

    /**
     * addTemplateAction
     * return json for store decoration page
     * @access public
     * @return object
     */
    public function addTemplateAction()
    {
        $data = $this->getRequest()->getPost();
        $segment = new Segment('customer');
        $store_id = $data['store_id'];
        $r = new Retailer();
        $r->load($segment->get('customer')['id'], 'customer_id');
        $data['store_id'] = $r['store_id'];
        $model = new StoreTemplate();
        if ($data['template_id'] == '0' || $store_id == '0') {
            $model->setData($data);
            $model->save();
            $template_id = $model->getId();
        } else {
            $template_id = $data['template_id'];
            unset($data['template_id']);
            $model->load($template_id);
            $model->setData($data);
            $model->save();
        }
        $result = ['status' => true, 'id' => $template_id, 'store_id' => $data['store_id']];
        echo json_encode($result);
    }

    public function delTemplateAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $segment = new Segment('customer');
            $r = new Retailer();
            $r->load($segment->get('customer')['id'], 'customer_id');
            $store_id = $r['store_id'];
            $model = new StoreTemplate();
            $model->load($data['id']);
            $result = $this->validateForm($data, ['id']);
            if ($model['store_id'] != $store_id) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please contact us or try again later.'), 'level' => 'success'];
            } else {
                $model->remove();
                $result['removeLine'] = 1;
                $result['message'][] = ['message' => $this->translate('Template has been deleted successfully.'), 'level' => 'success'];
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/store/decorationList/', 'retailer');
    }

    public function setTemplateAction()
    {
        $data = $this->getRequest()->getPost();
        $segment = new Segment('customer');
        $r = new Retailer();
        $r->load($segment->get('customer')['id'], 'customer_id');
        $store_id = $r['store_id'];
        $model = new StoreTemplate();
        $model->load($data['id']);
        if ($model['store_id'] != $store_id) {
            $result = ['status' => false];
        } else {
            $template = new StoreTemplateCollection();
            $template->storeTemplateList($store_id);
            foreach ($template as $key => $value) {
                $tempModel = new StoreTemplate();
                $tempModel->load($value['id']);
                $tempModel->setData(['status' => 0]);
                $tempModel->save();
            }
            $model->load($data['id'])->setData(['status' => 1]);
            $model->save();
            $result = ['status' => true];
        }
        return $this->response(['error' => 0, 'message' => []], 'retailer/store/decorationList/');
    }

    public function funcAction()
    {
        $functions = $this->getRequest()->getQuery('functions');
        $part_id = $this->getRequest()->getQuery('part_id');
        $template_id = $this->getRequest()->getQuery('template_id');
        $current_template_id = $this->getRequest()->getQuery('current_template_id');
        $root = $this->getLayout('decorationFunc_' . $functions);
        $root->getChild('main', true)->setVariable('data_tag', $functions);
        $root->getChild('main', true)->setVariable('part_id', $part_id);
        $root->getChild('main', true)->setVariable('template_id', $template_id);
        $root->getChild('main', true)->setVariable('current_template_id', $current_template_id);
        return $root;
    }

    public function getTemplateDataAction()
    {
        $dataParam = $this->getRequest()->getPost('dataParam');
        $dataTag = $this->getRequest()->getPost('dataTag');
        $storeDecoration = new StoreDecoration();
        $function_name = 'template_' . $dataTag;
        $view = $storeDecoration->$function_name($dataParam);
        echo json_encode(['status' => true, 'view' => $view]);
    }

    public function getProductInfoAction()
    {
        $result = ['error' => 0, 'message' => []];
        $data = $this->getRequest()->getPost();
        $data['page'] = isset($data['page']) ? $data['page'] : 1;
        $data['limit'] = isset($data['limit']) ? $data['limit'] : 20;
        $storeDecoration = new StoreDecoration();
        $products = $storeDecoration->func_getProductInfo($data);
        $result['status'] = 0;
        $result['Info'] = $products['data'];
        $result['count'] = $products['count'];
        $result['AllPage'] = ceil($products['count'] / $data['limit']);
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function customizeTemplateAddAction()
    {
        $result = ['error' => 0, 'message' => []];
        $data = $this->getRequest()->getPost();
        $segment = new Segment('customer');
        $r = new Retailer();
        $r->load($segment->get('customer')['id'], 'customer_id');
        $data['store_id'] = $r['store_id'];
        $model = new StoreTemplate();
        $template_id = 0;
        try {
            $model->setData($data);
            $model->save();
            $template_id = $model->getId();
        } catch (Exception $e) {
            $result['error'] = 1;
        }

        $result['status'] = $result['error'];
        $storeDecoration = new StoreDecoration();
        $result['Info'] = $storeDecoration->getCustomizeInfo($data['parent_id'], $data['page_type'], $data['store_id']);
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function customizeTemplateSaveAction()
    {
        $result = ['error' => 0, 'message' => []];
        $segment = new Segment('customer');
        $data = $this->getRequest()->getPost();
        $store_id = 0;
        if ($result['error'] === 0) {
            try {
                $template = new StoreTemplate();
                $template->load($data['id']);
                $r = new Retailer();
                $r->load($segment->get('customer')['id'], 'customer_id');
                $store_id = $r['store_id'];
                if ($template->getId() && $template['store_id'] == $r['store_id']) {
                    $template->setData([
                        'template_name' => $data['template_name']
                    ]);
                    $template->save();
                }
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);

                $result['error'] = 1;
            }
        }

        $result['status'] = $result['error'];
        $storeDecoration = new StoreDecoration();
        $result['Info'] = $storeDecoration->getCustomizeInfo($data['parent_id'], $data['page_type'], $store_id);
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function customizeTemplateDeleteAction()
    {
        $result = ['error' => 0, 'message' => []];
        $segment = new Segment('customer');
        $data = $this->getRequest()->getPost();
        $store_id = 0;
        if ($result['error'] === 0) {
            try {
                $template = new StoreTemplate();
                $template->load($data['id']);
                $r = new Retailer();
                $r->load($segment->get('customer')['id'], 'customer_id');
                $store_id = $r['store_id'];
                if ($template->getId() && $template['store_id'] == $r['store_id']) {
                    $template->remove();
                }
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
            }
        }

        $result['status'] = $result['error'];
        $storeDecoration = new StoreDecoration();
        $result['Info'] = $storeDecoration->getCustomizeInfo($data['parent_id'], $data['page_type'], $store_id);
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function decorationInfoAddAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $r = new Retailer();
            $r->load($segment->get('customer')['id'], 'customer_id');
            $store = $r['store_id'];
            $storePicinfo = new StorePicInfo();
            try {
                $storePicinfo->setData([
                    'store_id' => $store,
                    'pic_title' => $data['title'],
                    'url' => $data['url'],
                    'resource_category_code' => $data['resource_category_code'],
                    'resource_id' => null,
                    'sort_order' => 0
                ]);
                $storePicinfo->save();
            } catch (Exception $e) {
                $result['error'] = 1;
            }
            $storePicinfo->setData(['sort_order' => $storePicinfo->getId()]);
            $storePicinfo->save();
        }
        $storeDecoration = new StoreDecoration();
        $result['status'] = $result['error'];
        $result['Info'] = $storeDecoration->getStorePicInfo($data['resource_category_code']);
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function decorationInfoDeleteAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $r = new Retailer();
            $r->load($segment->get('customer')['id'], 'customer_id');
            $data = $this->getRequest()->getPost();
            $storePicinfo = new StorePicInfo();
            $storePicinfo->load($data['id']);
            if ($storePicinfo->getId() && $storePicinfo['store_id'] == $r['store_id']) {
                $storePicinfo->remove();
            } else {
                $result['error'] = 1;
            }
        }
        $storeDecoration = new StoreDecoration();
        $result['status'] = $result['error'];
        $result['Info'] = $storeDecoration->getStorePicInfo($data['resource_category_code']);
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function decorationUploadAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $current_template_id = empty($data['current_template_id']) ? null : $data['current_template_id'];
            $part_id = empty($data['part_id']) ? null : $data['part_id'];
            $files = $this->getRequest()->getUploadedFile()['files'];
            $r = new Retailer();
            $r->load($segment->get('customer')['id'], 'customer_id');
            $store = $r['store_id'];
            $result = $this->validateForm($data);
            if ($result['error'] === 0) {
                $categoryCollection = new Category();
                $categorys = $categoryCollection->getCategoryByCode($data['resource_category_code']);
                $category = (!empty($categorys) && isset($categorys[0]['id'])) ? $categorys[0]['id'] : null;
                try {
                    foreach ($files as $file) {
                        $name = $file->getClientFilename();
                        $model = new Resource();
                        $model->moveFile($file)
                                ->setData([
                                    'store_id' => $store,
                                    'uploaded_name' => $name,
                                    'file_type' => $file->getClientMediaType(),
                                    'category_id' => isset($data['category_id']) && $data['category_id'] ? $data['category_id'] : $category
                                ])->save();
                        $result['message'][] = ['message' => $this->translate('%s has been uploaded successfully.', [$name], 'resource'), 'level' => 'success'];
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                }
            }
            if ($result['error'] === 0) {
                $storePicinfo = new StorePicInfo();
                $storePicinfo->setData([
                    'store_id' => $store,
                    'pic_title' => $data['pic_title'],
                    'url' => $data['url'],
                    'template_id' => $current_template_id,
                    'part_id' => $part_id,
                    'resource_category_code' => $data['resource_category_code'],
                    'resource_id' => $model->getId(),
                    'sort_order' => $model->getId()
                ]);
                $storePicinfo->save();
            }
        }

        $storeDecoration = new StoreDecoration();
        $result['picInfo'] = $storeDecoration->getStorePicInfo($data['resource_category_code'], null, $current_template_id, $part_id);
        $result['status'] = $result['error'];
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function decorationUploadDeleteAction()
    {
        $result = ['error' => 0, 'message' => []];
        $segment = new Segment('customer');
        $data = $this->getRequest()->getPost();
        if ($result['error'] === 0) {
            try {
                $path = BP . Resource::$options['path'];

                $model = new Resource();
                $model->load($data['resource_id']);
                if ($model->getId()) {
                    $type = $model['file_type'];
                    $r = new Retailer();
                    $r->load($segment->get('customer')['id'], 'customer_id');
                    if ($model['store_id'] == $r['store_id']) {
                        $model->remove();
                    }
                    $storePicinfo = new StorePicInfo();
                    $storePicinfo->load($data['id']);
                    if ($storePicinfo->getId() && $storePicinfo['store_id'] == $r['store_id']) {
                        $storePicinfo->remove();
                    }
                }
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);

                $result['error'] = 1;
            }
        }
        $storeDecoration = new StoreDecoration();
        $result['status'] = $result['error'];
        $result['picInfo'] = $storeDecoration->getStorePicInfo($data['resource_category_code']);
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function decorationUploadSaveAction()
    {
        $result = ['error' => 0, 'message' => []];
        $segment = new Segment('customer');
        $data = $this->getRequest()->getPost();
        if ($result['error'] === 0) {
            try {
                $storePicinfo = new StorePicInfo();
                $storePicinfo->load($data['id']);
                $r = new Retailer();
                $r->load($segment->get('customer')['id'], 'customer_id');
                if ($storePicinfo->getId() && $storePicinfo['store_id'] == $r['store_id']) {
                    $storePicinfo->setData([
                        'pic_title' => $data['pic_title'],
                        'url' => $data['url']
                    ]);
                    $storePicinfo->save();
                }
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);

                $result['error'] = 1;
            }
        }
        $result['status'] = $result['error'];
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function decorationUploadForBannerAction()
    {
        $this->decorationDeleteForBanner();
        $result = ['error' => 0, 'message' => []];
        $name = '';
        if ($this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $files = $this->getRequest()->getUploadedFile()['files'];
            $r = new Retailer();
            $r->load($segment->get('customer')['id'], 'customer_id');
            $store = $r['store_id'];
            if ($result['error'] === 0) {
                try {
                    foreach ($files as $file) {
                        $name = $file->getClientFilename();
                        $model = new Resource();
                        $model->moveFile($file)
                                ->setData([
                                    'store_id' => $store,
                                    'uploaded_name' => $name,
                                    'file_type' => $file->getClientMediaType(),
                                    'category_id' => null
                                ])->save();
                        $name = $model['real_name'];
                        $result['message'][] = ['message' => $this->translate('%s has been uploaded successfully.', [$name], 'resource'), 'level' => 'success'];
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                }
            }
            if ($result['error'] === 0) {
                $Retailer = new Retailer();
                $Retailer->load($data['retailer_id']);
                if ($Retailer['store_id'] == $store) {
                    $Retailer->setData(['banner' => $model->getId()]);
                    $Retailer->save();
                }
            }
        }
        $result['picInfo'] = $name;
        $result['status'] = $result['error'];
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function decorationUploadDeleteForBannerAction()
    {
        $result = $this->decorationDeleteForBanner();
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function decorationDeleteForBanner()
    {
        $result = ['error' => 0, 'message' => []];
        $segment = new Segment('customer');
        $data = $this->getRequest()->getPost();
        if ($result['error'] === 0) {
            $storeDecoration = new StoreDecoration();
            $retailer = $storeDecoration->getStoreBanner();
            if (!empty($retailer['banner'])) {
                try {
                    $path = BP . Model::$options['path'];

                    $model = new Resource();
                    $model->load($retailer['banner']);
                    if ($model->getId()) {
                        $type = $model['file_type'];
                        $r = new Retailer();
                        $r->load($segment->get('customer')['id'], 'customer_id');
                        if ($model['store_id'] == $r['store_id']) {
                            $model->remove();
                        }
                    }
                    $Retailer = new Retailer();
                    $Retailer->load($retailer['id']);
                    $Retailer->setData(['banner' => null]);
                    $Retailer->save();
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);

                    $result['error'] = 1;
                }
            }
        }
        $result['status'] = $result['error'];
        return $result;
    }
}
