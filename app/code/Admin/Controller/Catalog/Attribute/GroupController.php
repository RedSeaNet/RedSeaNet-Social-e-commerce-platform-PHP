<?php

namespace Redseanet\Admin\Controller\Catalog\Attribute;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Eav\Type;

class GroupController extends AuthActionController
{
    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Lib\\Model\\Eav\\Attribute\\Group', ':ADMIN/catalog_attribute_set/');
    }

    public function saveAction()
    {
        return $this->doSave(
            '\\Redseanet\\Lib\\Model\\Eav\\Attribute\\Group',
            ':ADMIN/catalog_attribute_set/',
            ['name'],
            function ($model, $data) {
                $type = new Type();
                $type->load(Product::ENTITY_TYPE, 'code');
                $model->setData('type_id', $type->getId());
            }
        );
    }
}
