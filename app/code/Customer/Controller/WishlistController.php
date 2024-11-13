<?php

namespace Redseanet\Customer\Controller;

use Exception;
use Redseanet\Customer\Model\Wishlist as Model;
use Redseanet\Lib\Session\Segment;
use Redseanet\Customer\Model\Wishlist\Item;

class WishlistController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('customer_wishlist');
    }

    public function addAction()
    {
        $data = $this->getRequest()->isGet() ? $this->getRequest()->getQuery() : $this->getRequest()->getPost();
        $segment = new Segment('customer');
        $customerId = $segment->get('customer')['id'];
        try {
            $result = $this->validateForm($data, ['product_id', 'warehouse_id']);
            if (!$result['error']) {
                if (!isset($data['qty'])) {
                    $data['qty'] = 1;
                }
                $wishlist = new Model();
                $wishlist->load($customerId, 'customer_id');
                if (!$wishlist->getId()) {
                    $wishlist->load($wishlist->getId())->setData(['customer_id' => $customerId, 'id' => null])->save();
                }
                $result['data'] = ['wishlist_id' => $wishlist->getId()];
                $data['wishlist_id'] = $wishlist->getId();
                $wishlist->getId();
                $wishlist->addItem($data);
                $result['message'][] = ['message' => $this->translate('The product has been added to wishlist successfully.'), 'level' => 'success'];
            } else {
                $result['error'] = 1;
            }
        } catch (Exception $e) {
            $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
            $this->getContainer()->get('log')->logException($e);
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] ?: 'customer/wishlist/', 'customer');
    }

    public function commitAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['description']);
            if ($result['error'] === 0) {
                try {
                    foreach ((array) $data['description'] as $id => $description) {
                        $item = new Item();
                        $item->setData([
                            'id' => $id,
                            'description' => preg_replace('/\<[^\>]+\>/', '', $description)
                        ])->save();
                    }
                    $result['message'][] = ['message' => $this->translate('The description has been updated successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'success'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'customer/wishlist/', 'customer');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            $item = new Item();
            if ($result['error'] === 0) {
                try {
                    $item->setId($data['id'])->remove();
                    $result['removeLine'] = 1;
                    $result['message'][] = ['message' => $this->translate('The product has been removed from wishlist successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please contact us or try again later.'), 'level' => 'success'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'customer/wishlist/', 'customer');
    }
}
