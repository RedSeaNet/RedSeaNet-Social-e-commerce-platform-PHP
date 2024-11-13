<?php

namespace Redseanet\Retailer\Controller;

use Exception;
use Redseanet\Retailer\Model\Category as Model;

class CategoryController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('retailer_category');
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['name', 'uri_key']);
            try {
                $model = new Model($data);
                if (empty($data['id'])) {
                    $model->setId(null);
                }
                if (empty($data['parent_id']) ||
                        !empty($data['id']) && $data['parent_id'] == $data['id'] ||
                        (new Model())->load($data['parent_id'])['store_id'] != $this->getRetailer()['store_id']) {
                    $model->offsetSet('parent_id', null);
                }
                $model->setData([
                    'default_name' => $data['name'],
                    'store_id' => $this->getRetailer()['store_id']
                ])->offsetUnset('name');
                $model->save();
                $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('You add the category already exists, please re add.'), 'level' => 'danger'];
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'retailer');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            try {
                $model = new Model();
                $model->setId($data['id'])->remove();
                $result['reload'] = 1;
                $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please contact us or try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'retailer');
    }

    public function removeAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->isGet() ? $this->getRequest()->getQuery() : $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            try {
                $model = new Model();
                if (isset($data) && is_array($data)) {
                    foreach ((array) $data['id'] as $id) {
                        $model->setId($id)->remove();
                    }
                } else {
                    $model->setId($data['id'])->remove();
                }
                $result['reload'] = 1;
                $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please contact us or try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'retailer');
    }

    public function moveAction()
    {
        $result = 0;
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('order');
            try {
                foreach ((array) $data as $order => $id) {
                    $model = new Model();
                    $model->setId($id)
                            ->setData('sort_order', $order)
                            ->save();
                }
                $result = 1;
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
            }
        }
        return $result;
    }
}
