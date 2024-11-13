<?php

namespace Redseanet\Admin\Controller\Forum;

use Redseanet\Forum\Model\Category as Model;
use Redseanet\Lib\Controller\AuthActionController;

class CategoryController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_forum_category_list');
        return $root;
    }

    public function editAction()
    {
        $query = $this->getRequest()->getQuery();
        $root = $this->getLayout('admin_forum_category_edit');
        $model = new Model();
        if (isset($query['id'])) {
            $model->load($query['id']);
            $root->getChild('head')->setTitle('Edit Category / Forum Management');
        } else {
            $root->getChild('head')->setTitle('Add New Category / Forum Management');
        }
        $root->getChild('edit', true)->setVariable('model', $model);
        return $root;
    }

    public function orderAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $model = new Model();
            $this->beginTransaction();
            foreach ($data['id'] as $order => $id) {
                $model = clone $model;
                $model->load($id)->setData([
                    'sort_order' => $order,
                    'parent_id' => $data['order'][$order] ?: null
                ])->save();
            }
            $this->commit();
        }
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Forum\\Model\\Category', ':ADMIN/forum_category/');
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Forum\\Model\\Category', ':ADMIN/forum_category/', ['status', 'name']);
    }
}
