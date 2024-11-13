<?php

namespace Redseanet\Admin\Controller\Log;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;

class VisitorController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_log_visitor_list');
    }

    public function deleteAction()
    {
        return $this->doDelete('Redseanet\\Log\\Model\\Visitor', ':ADMIN/log_visitor/');
    }
}
