<?php

namespace Redseanet\Admin\Controller;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Promotion\Model\Coupon;
use Redseanet\Promotion\Model\Rule as Model;
use Laminas\Math\Rand;
use Redseanet\Admin\Model\User;

class PromotionController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_promotion_list');
    }

    public function editAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            if ((int) $model['use_coupon'] === 0) {
                $root = $this->getLayout('admin_promotion_edit_1');
                $root->getChild('edit', true)->setVariable('model', $model)
                        ->setVariable('title', 'Edit Promotion Activities');
                $root->getChild('head')->setTitle('Edit Promotion Activities / Promotion');
            } else {
                $root = $this->getLayout('admin_promotion_edit_2');
                $root->getChild('edit', true)->setVariable('model', $model)
                        ->setVariable('title', 'Edit Coupons');
                $root->getChild('head')->setTitle('Edit Coupons / Promotion');
            }
        } else {
            if ($this->getRequest()->getQuery('using')) {
                $root = $this->getLayout('admin_promotion_edit_2');
                $root->getChild('edit', true)->setVariable('title', 'Add New Coupons');
                $root->getChild('head')->setTitle('Add New Coupons / Promotion');
            } else {
                $root = $this->getLayout('admin_promotion_edit_1');
                $root->getChild('edit', true)->setVariable('title', 'Add New Promotion Activities');
                $root->getChild('head')->setTitle('Add New Promotion Activities / Promotion');
            }
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Promotion\\Model\\Rule', ':ADMIN/promotion/');
    }

    public function saveAction()
    {
        return $this->doSave(
            '\\Redseanet\\Promotion\\Model\\Rule',
            ':ADMIN/promotion/',
            ['name'],
            function ($model, $data) {
                $userArray = (new Segment('admin'))->get('user');
                $user = new User();
                $user->load($userArray['id']);
                if ($user->getStore()) {
                    if ($model->getId() && $model->offsetGet('store_id') != $user->getStore()->getId()) {
                        throw new \Exception('Not allowed to save.');
                    }
                    $model->setData('store_id', $user->getStore()->getId());
                } elseif (empty($data['store_id'])) {
                    $model->setData('store_id', null);
                }
                if (!isset($data['from_date']) || strtotime($data['from_date']) === false) {
                    $model->setData('from_date', date('Y-m-d H:i:s'));
                }
                if (!isset($data['to_date']) || strtotime($data['to_date']) === false) {
                    $model->setData('to_date', null);
                }
            }
        );
    }

    public function enableAction()
    {
        $data = $this->getRequest()->getQuery();
        $result = $this->validateForm($data, ['id']);
        if ($result['error'] === 0) {
            try {
                $model = new Model();
                $count = 0;
                foreach ((array) $data['id'] as $id) {
                    $model->setData(['id' => $id, 'status' => 1])->save();
                    $count++;
                }
                $result['message'][] = ['message' => $this->translate('%d item(s) have been enabled successfully.', [$count]), 'level' => 'success'];
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['message'][] = ['message' => $this->translate('An error detected while enabling. Please check the log report or try again.'), 'level' => 'danger'];
                $result['error'] = 1;
            }
        }
        return $this->response($result, ':ADMIN/promotion/');
    }

    public function disableAction()
    {
        $data = $this->getRequest()->getQuery();
        $result = $this->validateForm($data, ['id']);
        if ($result['error'] === 0) {
            try {
                $model = new Model();
                $count = 0;
                foreach ((array) $data['id'] as $id) {
                    $model->setData(['id' => $id, 'status' => 0])->save();
                    $count++;
                }
                $result['message'][] = ['message' => $this->translate('%d item(s) have been disabled successfully.', [$count]), 'level' => 'success'];
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['message'][] = ['message' => $this->translate('An error detected while disabling. Please check the log report or try again.'), 'level' => 'danger'];
                $result['error'] = 1;
            }
        }
        return $this->response($result, ':ADMIN/promotion/');
    }

    public function deleteCouponAction()
    {
        return $this->doDelete('\\Redseanet\\Promotion\\Model\\Coupon');
    }

    public function generateCouponAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $data = $this->getRequest()->getPost();
            if (!isset($data['coupon'])) {
                $data['coupon'] = ['code' => []];
            } elseif (!isset($data['count'])) {
                $data['count'] = 0;
            }
            $result = [];
            for ($i = 0; $i < $data['count'];) {
                $code = Rand::getString(10, 'abcdefghijkmnopqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWXYZ');
                if (!in_array($code, $result) && !in_array($code, $data['coupon']['code']) && !(new Coupon())->load($code, 'code')->getId()) {
                    $result[] = $code;
                    $i++;
                }
            }
            return $result;
        }
        return $this->notFoundAction();
    }
}
