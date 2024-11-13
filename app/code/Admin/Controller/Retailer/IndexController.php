<?php

namespace Redseanet\Admin\Controller\Retailer;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Model\Retailer;

class IndexController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        $root = $this->getLayout('admin_retailer_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_retailer_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Retailer();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Retailer');
        } else {
            $root->getChild('head')->setTitle('Add Retailer');
        }
        return $root;
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        $required = ['uri_key', 'store_id'];
        $data = $this->getRequest()->getPost();

        $result = $this->validateForm($data, $required);
        if ($result['error'] === 0) {
            $retailerData = [];
            if (isset($data['id']) && $data['id'] != '') {
                $retailerData['id'] = $data['id'];
            }
            $retailerData['store_id'] = $data['store_id'];
            $retailerData['description'] = (isset($data['description']) ? $data['description'] : '');
            $retailerData['keywords'] = (isset($data['keywords']) ? $data['keywords'] : '');
            $retailerData['address'] = (isset($data['address']) ? $data['address'] : '');
            $retailerData['tel'] = (isset($data['tel']) ? $data['tel'] : '');
            $retailerData['uri_key'] = (isset($data['uri_key']) ? $data['uri_key'] : '');

            $retailerObject = new Retailer($retailerData);
            $retailerObject->save();
            $this->flushList('core_store');
        }
        return $this->response($result, ':ADMIN/retailer_index/');
    }
}
