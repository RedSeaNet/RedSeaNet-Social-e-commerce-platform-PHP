<?php

namespace Redseanet\Admin\Controller\Catalog\Product;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Catalog\Model\Product\Review as Model;

class ReviewController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_catalog_product_review_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_catalog_product_review_edit');
        if ($query = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($query);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Review / Review Management');
        } else {
            $root->getChild('head')->setTitle('Add New Review / Review Management');
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Catalog\\Model\\Product\\Review', ':ADMIN/catalog_product_review/');
    }

    public function saveAction()
    {
        return $this->doSave(
            '\\Redseanet\\Catalog\\Model\\Product\\Review',
            ':ADMIN/catalog_product_review/',
            ['product_id'],
            function ($model, $data) {
                if ($data['customer_id'] === '') {
                    $model['customer_id'] = null;
                }
                if (empty($data['id'])) {
                    $model->setId(null);
                }
            }
        );
    }
}
