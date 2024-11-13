<?php

namespace Redseanet\Admin\Controller\Sales;

use Redseanet\Lib\Controller\AuthActionController;

class CartController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_sales_cart_list');
        return $root;
    }

    public function viewAction()
    {
        if ($id = $this->getRequest()->getQuery('id')) {
            return $this->getLayout('admin_sales_cart_view');
        }
        return $this->notFoundAction();
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Sales\\Model\\Cart', ':ADMIN/sales_cart/');
    }
}
