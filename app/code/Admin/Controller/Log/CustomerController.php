<?php

namespace Redseanet\Admin\Controller\Log;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;

class CustomerController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_log_customer_list');
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Log\\Model\\Customer', ':ADMIN/log_customer/');
    }
}
