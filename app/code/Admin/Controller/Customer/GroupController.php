<?php

namespace Redseanet\Admin\Controller\Customer;

use Exception;
use Redseanet\Customer\Model\Group as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;

class GroupController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        $root = $this->getLayout('admin_customer_group_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_customer_group_edit');
        $model = new Model();
        if ($id = $this->getRequest()->getQuery('id')) {
            $model->load($id);
            $root->getChild('head')->setTitle('Edit Customer Group / Customer Management');
        } else {
            $root->getChild('head')->setTitle('Add New Customer Group / Customer Management');
        }
        $root->getChild('edit', true)->setVariable('model', $model);
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Customer\\Model\\Group', ':ADMIN/customer_group/');
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Customer\\Model\\Group', ':ADMIN/customer_group/', ['name']);
    }

    public function customerListAction()
    {
        $root = $this->getLayout('admin_customer_group_customer');
        return $root;
    }

    public function addCustomerAction()
    {
        $root = $this->getLayout('admin_customer_group_addcustomer');
        return $root;
    }

    public function AddCustomerSaveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $required = ['customers', 'group'];
            $result = $this->validateForm($data, $required);
            if (0 === $result['error']) {
                if (!empty($data['customers']) && count($data['customers']) > 0) {
                    for ($c = 0; $c < count($data['customers']); $c++) {
                        $collection = new customerCollection();
                        $collection->join('customer_in_group', 'main_table.id=customer_in_group.customer_id', [], 'left');
                        $collection->where(['group_id' => $data['group'], 'customer_id' => $data['customers'][$c]]);
                        if (count($collection) == 0) {
                            $tableGateway = $this->getTableGateway('customer_in_group');
                            $tableGateway->insert(['group_id' => $data['group'], 'customer_id' => $data['customers'][$c]]);
                        }
                    }
                }
            }
        }
        return $this->response($result, ':ADMIN/customer_group/customerlist/?group_name=' . $data['group']);
    }

    public function DeleteCustomerInGroupAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['group_id', 'customer_id']);
            $tableGateway = $this->getTableGateway('customer_in_group');
            $tableGateway->delete(['group_id' => $data['group_id'], 'customer_id' => $data['customer_id']]);
            $this->flushList('customer');
            $result['reload'] = 1;
        }

        return $this->response($result, ':ADMIN/customer_group/customerlist/?group_name=' . $data['group_id']);
    }
}
