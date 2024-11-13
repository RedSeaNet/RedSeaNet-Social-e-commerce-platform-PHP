<?php

namespace Redseanet\Bulk\Controller;

use Redseanet\Retailer\Controller\AuthActionController;

class RetailerController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('retailer_sales_order_bulk');
        $root->addBodyClass('retailer-sales-order-list');
        return $root;
    }
}
