<?php

namespace Redseanet\Bulk\Controller;

use Exception;
use Redseanet\Catalog\Model\Product;
use Redseanet\Retailer\Controller\AuthActionController;

class PriceController extends AuthActionController
{
    public function editAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $product = new Product();
            $product->load($id);
            if ($product->getId()) {
                $root = $this->getLayout('bulk_price_edit');
                $root->getChild('main', true)->setVariable('product', $product);
                return $root;
            }
        }
        return $this->redirectReferer();
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Product();
                    $model->load($data['id']);
                    $model->setData([
                        'images' => json_encode($model['images']) ?: '[]',
                        'bulk_price' => $data['bulk_price'] ?? '',
                        'bulk_expiration' => $data['bulk_expiration'] ?? ''
                    ])->save();
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/product/selling/', 'retailer');
    }
}
