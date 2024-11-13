<?php

namespace Redseanet\Retailer\Controller;

use Exception;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Catalog\Model\Collection\Warehouse;
use Redseanet\Catalog\Model\Product as Model;
use Redseanet\Retailer\Model\Retailer as Retailer;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Eav\Attribute\Set;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Math\Rand;

class ProductController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DataCache;

    protected $searchable = null;

    public function sellingAction()
    {
        return $this->getLayout('retailer_selling_products');
    }

    public function stockAction()
    {
        return $this->getLayout('retailer_stock_products');
    }

    public function historyAction()
    {
        return $this->getLayout('retailer_history_products');
    }

    public function releaseAction()
    {
        $query = $this->getRequest()->getQuery();
        $model = new Model();
        if (isset($query['id'])) {
            $model->load($query['id']);
            $root = $this->getLayout('retailer_product_edit_' . $model['product_type_id']);
            $root->getChild('head')->setTitle('Edit Product');
            $root->getChild('content')->setVariable('title', 'Edit Product');
        } else {
            $model->setData('attribute_set_id', function () {
                $set = new Set();
                $set->join('eav_entity_type', 'eav_entity_type.id=eav_attribute_set.type_id', [], 'left')
                        ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
                return $set->load()[0]['id'];
            });
            $root = $this->getLayout(!isset($query['attribute_set']) || !isset($query['product_type']) ? 'retailer_product_edit_before' : 'retailer_product_edit_' . $query['product_type']);
            $root->getChild('head')->setTitle('Release Product');
            $root->getChild('content')->setVariable('title', 'Release Product');
        }
        $root->getChild('content')->getChild('main')->setVariable('model', $model);
        return $root;
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $attributes = new Attribute();
            $attributes->withSet()->where([
                'is_required' => 1,
                'eav_attribute_set.id' => $data['attribute_set_id'],
            ])->columns(['code'])
                    ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id AND eav_entity_type.id=eav_attribute_set.type_id', [], 'right')
                    ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
            $required = ['store_id', 'attribute_set_id'];
            $attributes->walk(function ($attribute) use (&$required) {
                $required[] = $attribute['code'];
            });
            $result = $this->validateForm($data, $required);
            $back_url = 'retailer/product/release/';
            if ($result['error'] === 0) {
                $model = new Model($this->getRequest()->getQuery('language_id', Bootstrap::getLanguage()->getId()), $data);
                if (empty($data['id'])) {
                    $model->setId(null);
                }
                $back_url = 'retailer/product/selling/';
                $tmpkey = Rand::getString(200);
                if (empty($data['uri_key'])) {
                    $model->setData('uri_key', $tmpkey);
                }
                $type = new Type();
                $type->load(Model::ENTITY_TYPE, 'code');
                $model->setData([
                    'type_id' => $type->getId()
                ]);
                $user = (new Segment('customer'))->get('customer');
                $retailer = new Retailer();
                $retailer->load($user['id'], 'customer_id');
                if ($retailer['store_id']) {
                    if ($model->getId() && $model->offsetGet('store_id') == $retailer['store_id']) {
                        $model->setData('store_id', $retailer['store_id']);
                    }
                }
                try {
                    $model->save();
                    if ($model->offsetGet('uri_key') === $tmpkey) {
                        $model->setData('uri_key', 'p-' . $model->getId())->save();
                    }
                    $languages = new Language();
                    $languages->columns(['id']);
                    $languages->load(true, false);
                    foreach ($languages as $language) {
                        $this->reindex($model->getId(), $language['id']);
                    }
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result, $back_url, 'retailer');
    }

    private function getSearchableAttributes()
    {
        if (is_null($this->searchable)) {
            $this->searchable = new Attribute();
            $this->searchable->columns(['code'])
                    ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id', [], 'right')
                    ->where(['eav_entity_type.code' => Model::ENTITY_TYPE, 'searchable' => 1]);
        }
        return $this->searchable;
    }

    private function reindex($id, $languageId)
    {
        $model = new Model($languageId);
        $model->load($id);
        $indexer = $this->getContainer()->get('indexer');
        $values = [];
        foreach ($model->getCategories() as $category) {
            if ($category['uri_key']) {
                $record = $indexer->select('catalog_url', $languageId, ['category_id' => $category->getId(), 'product_id' => null]);
                if (count($record)) {
                    $values[] = ['category_id' => $category->getId(), 'product_id' => $id, 'path' => $record[0]['path'] . '/' . $model['uri_key']];
                }
            }
        }
        $indexer->replace('catalog_url', $languageId, $values, ['product_id' => $id]);
        $data = ['id' => $id, 'store_id' => $model['store_id'], 'data' => '|'];
        foreach ($this->getSearchableAttributes() as $attr) {
            $value = $model[$attr['code']];
            if ($value !== '' && $value !== null) {
                $data['data'] .= $value . '|';
            }
        }
        $indexer->replace('catalog_search', $languageId, [$data], ['id' => $id]);
    }

    public function withdrawAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                $warehouses = new Warehouse();
                $count = 0;
                $result['removeLine'] = [];
                try {
                    foreach ((array) $data['id'] as $id) {
                        $product = new Model();
                        $product->load($id);
                        if ($product->getId() && $product['store_id'] == $this->getRetailer()['store_id']) {
                            foreach ($warehouses as $warehouse) {
                                $warehouse->setInventory(['status' => 0] + $warehouse->getInventory($id));
                            }
                        }
                        $result['removeLine'][] = $id;
                        $count++;
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                } finally {
                    $this->flushList(Model::ENTITY_TYPE);
                    $result['message'][] = ['message' => $this->translate('%d product(s) have been withdrawed successfully.', [$count]), 'level' => 'success'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/account/', 'retailer');
    }

    public function replenishAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                $warehouses = new Warehouse();
                $count = 0;
                $result['removeLine'] = [];
                try {
                    foreach ((array) $data['id'] as $id) {
                        $product = new Model();
                        $product->load($id);
                        if ($product->getId() && $product['store_id'] == $this->getRetailer()['store_id']) {
                            foreach ($warehouses as $warehouse) {
                                $warehouse->setInventory(['status' => 1] + $warehouse->getInventory($id));
                            }
                        }
                        $result['removeLine'][] = $id;
                        $count++;
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                } finally {
                    $this->flushList(Model::ENTITY_TYPE);
                    $result['message'][] = ['message' => $this->translate('%d product(s) have been replenished successfully.', [$count]), 'level' => 'success'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/account/', 'retailer');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                $count = 0;
                $result['removeLine'] = [];
                try {
                    foreach ((array) $data['id'] as $id) {
                        $product = new Model();
                        $product->load($id);
                        $product->setData('status', 0)->save();
                        $result['removeLine'][] = $id;
                        $count++;
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please contact us or try again later.'), 'level' => 'danger'];
                } finally {
                    $result['message'][] = ['message' => $this->translate('%d product(s) have been deleted successfully.', [$count]), 'level' => 'success'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/account/', 'retailer');
    }

    public function resellAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                $count = 0;
                $result['removeLine'] = [];
                try {
                    foreach ((array) $data['id'] as $id) {
                        $product = new Model();
                        $product->load($id);
                        $product->setData('status', 1)->save();
                        $result['removeLine'][] = $id;
                        $count++;
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                } finally {
                    $result['message'][] = ['message' => $this->translate('%d product(s) will been reselled.', [$count]), 'level' => 'success'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/account/', 'retailer');
    }

    public function recommendAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                $count = 0;
                $result['removeLine'] = [];
                try {
                    foreach ((array) $data['id'] as $id) {
                        $product = new Model();
                        $product->load($id);
                        $product->setData('recommended', 1)->save();
                        $count++;
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                } finally {
                    $result['message'][] = ['message' => $this->translate('%d product(s) have been recommended successfully.', [$count]), 'level' => 'success'];
                }
            }
        }
        $result['reload'] = 1;
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/account/', 'retailer');
    }

    public function cancelRecommendAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                $count = 0;
                $result['removeLine'] = [];
                try {
                    foreach ((array) $data['id'] as $id) {
                        $product = new Model();
                        $product->load($id);
                        $product->setData('recommended', 0)->save();
                        $count++;
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                } finally {
                    $result['message'][] = ['message' => $this->translate('%d product(s) have been canceled recommendation successfully.', [$count]), 'level' => 'success'];
                }
            }
        }
        $result['reload'] = 1;
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/account/', 'retailer');
    }
}
