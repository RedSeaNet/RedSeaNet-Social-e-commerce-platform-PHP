<?php

namespace Redseanet\Admin\Controller;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;
use Redseanet\Bargain\Model\Bargain as Model;
use Redseanet\Bargain\Model\BargainCase;
use Redseanet\Bargain\Model\BargainCaseHelp;
use Redseanet\Catalog\Model\Collection\Product as productCollection;
use Redseanet\Catalog\Model\Product as productModel;
use Redseanet\Catalog\Model\Product\Option;

class BargainController extends AuthActionController
{
    use \Redseanet\Lib\Traits\Filter;

    public function indexAction()
    {
        return $this->getLayout('admin_bargain_list');
    }

    public function editAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $product = new productModel();
            $product->load($model['product_id']);

            $root = $this->getLayout('admin_bargain_edit');
            $root->getChild('edit', true)->setVariable('model', $model)
                    ->setVariable('title', 'Edit Bargain');
            $root->getChild('head')->setTitle('Edit Bargain / Bargain');

            $root->getChild('productoptions', true)->setVariable('product', $product)->setVariable('optioned', json_decode($model['options'], true));
        } else {
            $root = $this->getLayout('admin_bargain_edit');

            $root->getChild('edit', true)->setVariable('title', 'Add New Bargain');
            $root->getChild('head')->setTitle('Add New Bargain / Bargain');
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Bargain\\Model\\Bargain', ':ADMIN/bargain/');
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        $required = ['product_id', 'price', 'min_price'];
        $data = $this->getRequest()->getPost();

        $result = $this->validateForm($data, $required);
        if ($result['error'] === 0) {
            $bargainData = [];
            if (isset($data['id']) && $data['id'] != '') {
                $bargainData['id'] = $data['id'];
            }

            $bargainData['product_id'] = $data['product_id'];
            $bargainData['stock'] = (isset($data['stock']) ? $data['stock'] : 0);
            $bargainData['start_time'] = (isset($data['start_time']) ? $data['start_time'] : date('Y-m-d H:i:s'));
            $bargainData['stop_time'] = (isset($data['stop_time']) ? $data['stop_time'] : date('Y-m-d H:i:s'));
            $bargainData['price'] = (isset($data['price']) ? $data['price'] : 0);
            $bargainData['min_price'] = (isset($data['min_price']) ? $data['min_price'] : 0);
            $bargainData['num'] = (isset($data['num']) ? $data['num'] : 0);
            $bargainData['bargain_max_price'] = (isset($data['bargain_max_price']) ? $data['bargain_max_price'] : 0);
            $bargainData['bargain_min_price'] = (isset($data['bargain_min_price']) ? $data['bargain_min_price'] : 0);
            $bargainData['bargain_num'] = (isset($data['bargain_num']) ? $data['bargain_num'] : 0);
            $bargainData['status'] = $data['status'];
            $bargainData['original_price'] = (isset($data['original_price']) && $data['original_price'] != '' ? $data['original_price'] : 0);
            $bargainData['sort_order'] = (isset($data['sort_order']) && $data['sort_order'] != '' ? $data['sort_order'] : 0);
            $bargainData['is_recommend'] = $data['is_recommend'];
            $bargainData['people_num'] = (isset($data['people_num']) ? $data['people_num'] : 0);
            $bargainData['thumbnail'] = $data['thumbnail'];
            $bargainData['images'] = json_encode($data['images']);
            $bargainData['name'] = $data['name'];
            $bargainData['description'] = $data['description'];
            $bargainData['content'] = $data['content'];
            $bargainData['store_id'] = $data['store_id'];
            $bargainData['weight'] = $data['weight'];
            $bargainData['warehouse_id'] = $data['warehouse_id'];
            $bargainData['free_shipping'] = $data['free_shipping'];

            $product = new productModel();
            $product->load($data['product_id']);
            $options = $product->getOptions(['is_required' => 1]);
            foreach ($options as $option) {
                if (!isset($data['options'][$option->getId()])) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => sprintf($this->translate('The %s field is required and cannot be empty.'), $option->offsetGet('title')), 'level' => 'danger'];
                }
            }
            if ($result['error'] === 1) {
                return $this->response($result, ':ADMIN/bargain/edit/');
            }
            $optionsData = (isset($data['options']) && is_array($data['options']) && count($data['options']) > 0 ? $data['options'] : []);
            ksort($optionsData);

            $sku = $product['sku'];
            foreach ($optionsData as $key => $value) {
                $option = new Option();
                $option->load($key);
                if (in_array($option->offsetGet('input'), ['select', 'radio', 'checkbox', 'multiselect'])) {
                    $value = $option->getValue($value, false);
                    if (isset($value['sku']) && $value['sku'] !== '') {
                        $sku .= '-' . $option->getValue($value, false)['sku'];
                    }
                } elseif ($value !== '' && $option['sku'] !== '') {
                    $sku .= '-' . $option['sku'];
                }
            }
            $bargainData['sku'] = $sku;
            $bargainData['options'] = json_encode($optionsData);

            $bargainObject = new Model($bargainData);
            $bargainObject->save();
        }
        return $this->response($result, ':ADMIN/bargain/');
    }

    public function productListAction()
    {
        $data = $this->getRequest()->getQuery();
        $queryData = $data;
        $productCollection = new productCollection();
        if (!empty($queryData['name'])) {
            $productCollection->where("name like '%" . $queryData['name'] . "%'");
        }
        unset($queryData['name']);
        $this->filter($productCollection, $queryData);
        $root = $this->getLayout('admin_bargain_product_list');
        $root->getChild('main', true)->setVariable('collection', $productCollection)->setVariable('query', $data);

        return $root;
    }

    public function productOptionAction()
    {
        $data = $this->getRequest()->getQuery();
        $productId = (isset($data['id']) ? $data['id'] : '');
        if ($productId != '') {
            $root = $this->getLayout('admin_bargain_product_option');
            $product = new productModel();
            $product->load($productId);
            $root->getChild('main', true)->setVariable('product', $product);
            return $root;
        } else {
            return '';
        }
    }

    public function bargainCaseListAction()
    {
        $root = $this->getLayout('admin_bargain_case_list');
        return $root;
    }

    public function bargainCaseHelpListAction()
    {
        $root = $this->getLayout('admin_bargain_case_help_list');
        return $root;
    }
}
