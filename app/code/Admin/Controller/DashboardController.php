<?php

namespace Redseanet\Admin\Controller;

use Redseanet\Log\Model\Collection\Visitor;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Expression;

class DashboardController extends AuthActionController
{
    use \Redseanet\Admin\Traits\Stat;

    public function indexAction()
    {
        return $this->getLayout('admin_dashboard');
    }

    public function visitorsAction()
    {
        $collection = new Visitor();
        if ($this->getRequest()->getQuery('unique', false)) {
            $collection->group('session_id');
        }
        $collection->columns([new Expression('1')]);
        $segment = new Segment('admin');
        if ($id = $segment->get('user')['store_id']) {
            $collection->where(['store_id' => $id]);
        }
        return $this->stat(
            $collection,
            function ($collection) {
                return count($collection);
            }
        );
    }
}
