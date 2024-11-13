<?php

namespace Redseanet\Admin\Controller\Catalog;

use Exception;
use Redseanet\Catalog\Model\Product as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Eav\Attribute\Set;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;
use Redseanet\Admin\Model\User;

class ProductController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DataCache;

    private $searchable = null;

    public function indexAction()
    {
        $root = $this->getLayout('admin_catalog_product_list');
        return $root;
    }

    public function listAction()
    {
        $type = $this->getRequest()->getQuery('linktype');
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $segment = new Segment('admin');
            $old = $segment->get('form_data_product_link', [$type => ['remove' => [], 'product_link' => []]]);
            $old[$type]['remove'] = array_merge($old[$type]['remove'], array_diff(explode(',', $data['ids'][$type]), ($data['product_link'][$type] ?? [])));
            $old[$type]['product_link'] = array_merge($data['product_link'][$type] ?? [], $old[$type]['product_link']);
            $segment->set('form_data_product_link', $old);
        }
        $root = $this->getLayout('admin_catalog_product_simple_list');
        return $root;
    }

    public function editAction()
    {
        $query = $this->getRequest()->getQuery();
        $model = new Model();
        if (isset($query['id'])) {
            $model->load($query['id']);
            $root = $this->getLayout('admin_catalog_product_edit_' . $model['product_type_id']);
            $root->getChild('head')->setTitle('Edit Product / Product Management');
        } else {
            $model->setData('attribute_set_id', function () {
                $set = new Set();
                $set->join('eav_entity_type', 'eav_entity_type.id=eav_attribute_set.type_id', [], 'left')
                        ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
                return $set->load()[0]['id'];
            });
            $root = $this->getLayout(!isset($query['attribute_set']) || !isset($query['product_type']) ? 'admin_catalog_product_beforeedit' : 'admin_catalog_product_edit_' . $query['product_type']);
            $root->getChild('head')->setTitle('Add New Product / Product Management');
        }
        $root->getChild('edit', true)->setVariable('model', $model);
        if (!empty($model['name'])) {
            $root->getChild('breadcrumb', true)->addCrumb(['link' => 'catalog_product/edit/?id=' . $model['id'], 'label' => $model['name']]);
        }
        if (!empty($model['id'])) {
            $root->getChild('breadcrumb', true)->addCrumb(['label' => 'Edit']);
        } else {
            $root->getChild('breadcrumb', true)->addCrumb(['label' => 'Add']);
        }
        $segment = new Segment('admin');
        $segment->offsetUnset('form_data_product_link');
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Catalog\\Model\\Product', ':ADMIN/catalog_product/');
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
            if ($result['error'] === 0) {
                $model = new Model($this->getRequest()->getQuery('language_id', Bootstrap::getLanguage()->getId()), $data);
                if (empty($data['id'])) {
                    $model->setId(null);
                }
                $tmpkey = Rand::getString(200);
                if (empty($data['uri_key'])) {
                    $model->setData('uri_key', $tmpkey);
                }
                $type = new Type();
                $type->load(Model::ENTITY_TYPE, 'code');
                $model->setData([
                    'type_id' => $type->getId()
                ]);
                $userArray = (new Segment('admin'))->get('user');
                $user = new User();
                $user->load($userArray['id']);
                if ($user->getStore()) {
                    if ($model->getId() && $model->offsetGet('store_id') != $user->getStore()->getId()) {
                        return $this->redirectReferer();
                    }
                    $model->setData('store_id', $user->getStore()->getId());
                }
                try {
                    $entity_cloumn = ['type_id', 'attribute_set_id', 'store_id', 'product_type_id', 'status'];
                    $multilingual = [];
                    if (isset($data['_changed_fields'])) {
                        if (is_array($data['_changed_fields'])) {
                            $multilingual = array_intersect($entity_cloumn, $data['_changed_fields']);
                        } elseif (is_string($data['_changed_fields'])) {
                            $multilingual = array_intersect($entity_cloumn, explode(',', $data['_changed_fields']));
                        }
                    }
                    if (count($multilingual) > 0) {
                        $multilinguals = [];
                        $multilingualTmp = [];
                        foreach ($multilingual as $multilingualValue) {
                            $multilingualTmp[$multilingualValue] = $model[$multilingualValue];
                        }
                        $languages = new Language();
                        $languages->columns(['id']);
                        foreach ($languages as $language) {
                            $multilinguals[$language['id']] = $multilingualTmp;
                        }
                        $model->save([], false, $multilinguals);
                    } else {
                        $model->save();
                    }
                    if ($model->offsetGet('uri_key') === $tmpkey) {
                        $model->setData('uri_key', 'p-' . $model->getId())->save();
                    }

                    if (isset($data['_changed_fields']) && in_array('product_link', explode(',', $data['_changed_fields'] ?? []))) {
                        $segment = new Segment('admin');
                        $old = $segment->get('form_data_product_link', [
                            'related' => ['remove' => [], 'product_link' => []],
                            'upsells' => ['remove' => [], 'product_link' => []],
                            'crosssells' => ['remove' => [], 'product_link' => []],
                        ]);
                        $tableGateway = $this->getTableGateway('product_link');
                        foreach ($old as $type => $link) {
                            $link['remove'] = array_merge($link['remove'], array_diff(explode(',', $data['ids'][$type]), ($data['product_link'][$type] ?? [])));
                            if (count($link['remove'])) {
                                $tableGateway->delete(['product_id' => $model->getId(), 'linked_product_id' => $link['remove'], 'type' => substr($type, 0, 1)]);
                            }
                            foreach (array_merge($data['product_link'][$type] ?? [], $link['product_link']) as $order => $id) {
                                $this->upsert(['sort_order' => $order], [
                                    'product_id' => $model->getId(),
                                    'linked_product_id' => $id,
                                    'type' => substr($type, 0, 1)
                                ], $tableGateway);
                            }
                        }
                        $this->flushList(Model::ENTITY_TYPE);
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
        return $this->response($result, ':ADMIN/catalog_product/?page=' . (!empty($data['page']) ? $data['page'] : 1));
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

    public function productJsonAction()
    {
        $query = $this->getRequest()->getQuery();
        if (isset($query['id'])) {
            $product = new Model();
            $productId = $query['id'];
            $product->load($productId);
            if ($product->getId()) {
                $options = $product->getOptionsAndValues();
                $productArray = $product->toArray();

                $productArray['options'] = $options;
                $names = [];
                $descriptions = [];
                $short_descriptions = [];
                foreach (new Language() as $language) {
                    $productTmp = new Model($language->getId());
                    $productTmp->load($productId);
                    $names[] = ['langid' => $language->getId(), 'name' => $productTmp->name];
                    $descriptions[] = ['langid' => $language->getId(), 'description' => $productTmp->description];
                    $short_descriptions[] = ['langid' => $language->getId(), 'short_description' => $productTmp->short_description];
                }
                $productArray['names'] = $names;
                $productArray['descriptions'] = $descriptions;
                $productArray['short_descriptions'] = $short_descriptions;
                return $productArray;
            } else {
                return [];
            }
        }
        return [];
    }

    public function productlinkAction()
    {
        $root = $this->getLayout('admin_catalog_product_link');
        return $root;
    }

    public function productlinkdeleteAction()
    {
        $data = $this->getRequest()->getPost();
        $result = $this->validateForm($data, ['product_id', 'type', 'linked_product_id']);
        if ($result['error'] === 0) {
            try {
                $tableGateway = $this->getTableGateway('product_link');
                $delete = $tableGateway->getSql()->delete();
                $delete->where(['product_id' => $data['product_id'], 'type' => $data['type'], 'linked_product_id' => $data['linked_product_id']]);
                $tableGateway->deleteWith($delete);
                $result['message'][] = ['message' => $this->translate('%d item(s) have been deleted successfully.', [1]), 'level' => 'success'];
                //$result['removeLine'] = (array) $data['id'];
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please check the log report or try again.'), 'level' => 'danger'];
                $result['error'] = 1;
            }
        }
        $result['reload'] = 1;
        $this->flushList('product_link');
        $this->flushList(Model::ENTITY_TYPE);
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function productlinksaveAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $required = ['id'];
            $result = $this->validateForm($data, $required);
            if ($result['error'] === 0) {
                try {
                    $segment = new Segment('admin');
                    $old = $segment->get('form_data_product_link', [
                        'related' => ['remove' => [], 'product_link' => []],
                        'upsells' => ['remove' => [], 'product_link' => []],
                        'crosssells' => ['remove' => [], 'product_link' => []],
                    ]);
                    $tableGateway = $this->getTableGateway('product_link');
                    foreach ($old as $type => $link) {
                        $link['remove'] = array_merge($link['remove'], array_diff(!empty($data['ids'][$type]) ? explode(',', $data['ids'][$type]) : [], ($data['product_link'][$type] ?? [])));
                        if (count($link['remove'])) {
                            $tableGateway->delete(['product_id' => $data['id'], 'linked_product_id' => $link['remove'], 'type' => substr($type, 0, 1)]);
                        }
                        foreach (array_merge($data['product_link'][$type] ?? [], $link['product_link']) as $order => $id) {
                            $this->upsert(['sort_order' => $order], [
                                'product_id' => $data['id'],
                                'linked_product_id' => $id,
                                'type' => substr($type, 0, 1)
                            ], $tableGateway);
                        }
                    }
                    $this->flushList(Model::ENTITY_TYPE);
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result, ':ADMIN/catalog_product/productlink/?id=' . $data['id'] . '&type=' . substr($data['linktype'], 0, 1));
    }
}
