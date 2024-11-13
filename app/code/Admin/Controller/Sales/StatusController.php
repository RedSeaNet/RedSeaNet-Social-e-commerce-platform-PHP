<?php

namespace Redseanet\Admin\Controller\Sales;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Sales\Model\Order\Status as Model;

class StatusController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_order_status_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_order_status_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Status / Orders / Sales');
        } else {
            $root->getChild('head')->setTitle('Add New Status / Orders / Sales');
        }
        return $root;
    }

    public function deleteAction()
    {
        $model = new Model();
        $model->load($this->getRequest()->getPost('id'));
        if ($model->offsetGet('is_default')) {
            return $this->redirectReferer(':ADMIN/sales_status/');
        }
        return $this->doDelete($model, ':ADMIN/sales_status/');
    }

    public function saveAction()
    {
        return $this->doSave(
            '\\Redseanet\\Sales\\Model\\Order\\Status',
            ':ADMIN/sales_status/',
            ['name'],
            function ($model, $data) {
                if (!isset($data['is_default'])) {
                    $model->setData('is_default', 0);
                }
            }
        );
    }
}
