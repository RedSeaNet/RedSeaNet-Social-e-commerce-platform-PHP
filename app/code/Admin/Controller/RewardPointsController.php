<?php

namespace Redseanet\Admin\Controller;

use DOMDocument;
use DOMXPath;
use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\ViewModel\Template;
use Laminas\Db\Sql\Expression;
use Laminas\Stdlib\SplQueue;
use Redseanet\RewardPoints\Model\Collection\Record as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Bootstrap;

class RewardPointsController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        $root = $this->getLayout('admin_rewardpoints_list');
        return $root;
    }

    public function statisticsWithCustomerAction()
    {
        $root = $this->getLayout('admin_rewardpoints_statistics_customer_list');
        return $root;
    }
}
