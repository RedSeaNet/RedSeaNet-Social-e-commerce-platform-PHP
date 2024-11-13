<?php

namespace Redseanet\Admin\Controller\Retailer;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Model\Application;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Admin\Model\User;

class ApplyController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_retailer_apply_list');
        return $root;
    }

    public function editAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            $root = $this->getLayout('admin_retailer_apply_edit');
            $model = new Application();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Application');
            return $root;
        }
        return $this->notFoundAction();
    }

    public function saveAction()
    {
        return $this->doSave(
            '\\Redseanet\\Retailer\\Model\\Application',
            ':ADMIN/retailer_apply/',
            ['customer_id', 'status'],
            function ($model, $data) {
                $userArray = (new Segment('admin'))->get('user');
                $user = new User();
                $user->load($userArray['id']);
                if ($user->getStore()) {
                    throw new \Exception('Not allowed to save.');
                }
                $store = new Store();
                $code = 'retailer-' . $data['customer_id'];
                $store->load($code, 'code');
                $store->setData('status', $data['status'] ? 1 : 0);
                if ($data['status']) {
                    if (!$store->getId()) {
                        $customer = new Customer();
                        $customer->load($data['customer_id']);
                        $store->setData([
                            'id' => null,
                            'merchant_id' => $customer->getStore()['merchant_id'],
                            'code' => $code,
                            'name' => $customer['username'] . '\'s Store',
                            'is_default' => 0,
                            'status' => 1
                        ])->save();
                        $retailer = new Retailer();
                        $retailer->setData([
                            'customer_id' => $data['customer_id'],
                            'store_id' => $store->getId(),
                            'name' => $customer['username'],
                            'uri_key' => $customer['username']
                        ])->save();
                    } elseif (!$store['status']) {
                        $store->setData('status', 1)->save();
                    }
                } elseif ($store->getId()) {
                    $store->setData('status', 0)->save();
                }
            },
            true
        );
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Retailer\\Model\\Application', ':ADMIN/retailer_apply/');
    }
}
