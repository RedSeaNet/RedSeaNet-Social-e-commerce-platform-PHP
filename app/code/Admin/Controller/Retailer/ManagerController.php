<?php

namespace Redseanet\Admin\Controller\Retailer;

use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Store;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Model\Manager;
use Laminas\Db\Sql\Select;

class ManagerController extends AuthActionController
{
    use \Redseanet\Lib\Traits\Filter;

    public function indexAction()
    {
        $root = $this->getLayout('admin_retailer_manager_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_retailer_manager_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Manager();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Manager / Retailer');
        }
        $root->getChild('head')->setTitle('Add New Manager / Retailer');
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Retailer\\Model\\Manager', ':ADMIN/retailer_manager/');
    }

    public function saveAction()
    {
        return $this->doSave('Redseanet\\Retailer\\Model\\Manager', ':ADMIN/retailer_manager/', ['customer_id', 'retailer_id'], function ($model, $data) {
        });
    }

    public function getNotManagerCustomerListAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $collection = new customerCollection();
            $managers = new Select('retailer_manager');
            $managers->columns(['customer_id']);
            $collection->where('1=1')
                    ->where
                    ->notIn('id', $managers);
            if (!empty($data['q'])) {
                $collection->where("username like '%" . addslashes($data['q']) . "%'");
            }
            $result = [];
            $result['total_count'] = count($collection);
            $this->filter($collection);
            $result['results'] = [];
            $result['pagination']['more'] = false;
            if (count($collection) > 0) {
                for ($c = 0; $c < count($collection); $c++) {
                    $result['results'][] = ['id' => $collection[$c]['id'], 'text' => $collection[$c]['username']];
                }
            }
            return json_encode($result);
        }
        return [];
    }
}
