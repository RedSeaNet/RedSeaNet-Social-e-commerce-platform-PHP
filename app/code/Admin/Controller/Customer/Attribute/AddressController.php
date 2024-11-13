<?php

namespace Redseanet\Admin\Controller\Customer\Attribute;

use Redseanet\Customer\Model\Address;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Eav\Attribute as Model;
use Redseanet\Lib\Model\Eav\Type;
use Redseanet\Lib\Model\Eav\Attribute\Group;
use Redseanet\Lib\Model\Eav\Attribute\Set;

class AddressController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_address_attribute_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_address_attribute_edit');
        $model = new Model();
        if ($id = $this->getRequest()->getQuery('id')) {
            $model->load($id);
            $root->getChild('head')->setTitle('Edit Address Attribute / Customer Management');
        } else {
            $root->getChild('head')->setTitle('Add New Address Attribute / Customer Management');
        }
        $root->getChild('edit', true)->setVariable('model', $model);
        $root->getChild('label', true)->setVariable('model', $model);
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Lib\\Model\\Eav\\Attribute', ':ADMIN/customer_address/');
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data);
            if ($result['error'] === 0) {
                $model = new Model($data);
                try {
                    $this->beginTransaction();
                    $type = new Type();
                    $type->load(Address::ENTITY_TYPE, 'code');
                    if (empty($data['id'])) {
                        $model->setId(null);
                    }
                    $model->setData('type_id', $type->getId());
                    $model->save();
                    if (empty($data['id'])) {
                        $set = new Set();
                        $set->load($type->getId(), 'type_id');
                        $group = new Group();
                        $group->load($type->getId(), 'type_id');
                        $tableGateway = $this->getTableGateway('eav_entity_attribute');
                        $tableGateway->insert([
                            'attribute_set_id' => $set->getId(),
                            'attribute_group_id' => $group->getId(),
                            'attribute_id' => $model->getId(),
                            'sort_order' => 0
                        ]);
                    }
                    $this->commit();
                    $result['data'] = $model->getArrayCopy();
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->rollback();
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], ':ADMIN/customer_address/');
    }
}
