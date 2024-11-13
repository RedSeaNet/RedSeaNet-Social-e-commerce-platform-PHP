<?php

namespace Redseanet\Admin\Controller;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;

class BulkController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_bulk_list');
    }

    public function bulkingAction()
    {
        return $this->getLayout('admin_bulking_list');
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Bulk\\Model\\Bulk', ':ADMIN/bulk/bulking/');
    }
}
