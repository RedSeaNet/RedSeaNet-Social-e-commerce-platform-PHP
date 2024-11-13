<?php

namespace Redseanet\Admin\Controller\Catalog;

use Exception;
use Redseanet\Catalog\Model\Warehouse as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;

class WarehouseController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        $root = $this->getLayout('admin_catalog_warehouse_list');
        return $root;
    }

    public function editAction()
    {
        $query = $this->getRequest()->getQuery();
        $root = $this->getLayout('admin_catalog_warehouse_edit');
        $model = new Model();
        if (isset($query['id'])) {
            $model->load($query['id']);
            $root->getChild('head')->setTitle('Edit Warehouse / Warehouse Management');
            $root->getChild('edit', true)->setVariable('model', $model);
        } else {
            $root->getChild('head')->setTitle('Add New Warehouse / Warehouse Management');
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Catalog\\Model\\Warehouse', ':ADMIN/catalog_warehouse/');
    }

    public function saveAction()
    {
        return $this->doSave('Redseanet\\Catalog\\Model\\Warehouse', ':ADMIN/catalog_warehouse/', ['name', 'status'], function ($model, $data) {
        });
    }
}
