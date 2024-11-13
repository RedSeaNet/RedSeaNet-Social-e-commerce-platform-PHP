<?php

namespace Redseanet\Retailer\Controller;

use Redseanet\Log\Model\Collection\Visitor;
use Laminas\Db\Sql\Expression;

class DashboardController extends AuthActionController
{
    use \Redseanet\Admin\Traits\Stat;

    public function visitorsAction()
    {
        $collection = new Visitor();
        $collection->group('session_id')
                ->columns([new Expression('1')])
                ->where(['store_id' => $this->getRetailer()->offsetGet('store_id')]);
        return $this->stat(
            $collection,
            function ($collection) {
                return count($collection);
            }
        );
    }
}
