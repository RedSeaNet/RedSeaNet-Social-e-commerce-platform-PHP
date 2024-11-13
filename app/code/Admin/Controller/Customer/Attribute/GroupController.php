<?php

namespace Redseanet\Admin\Controller\Customer\Attribute;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Eav\Type;

class GroupController extends AuthActionController
{
    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Lib\\Model\\Eav\\Attribute\\Group', ':ADMIN/customer_attribute_set/');
    }

    public function saveAction()
    {
        return $this->doSave(
            '\\Redseanet\\Lib\\Model\\Eav\\Attribute\\Group',
            ':ADMIN/customer_attribute_set/',
            ['name'],
            function ($model, $data) {
                $type = new Type();
                $type->load(Customer::ENTITY_TYPE, 'code');
                $model->setData('type_id', $type->getId());
            }
        );
    }
}
