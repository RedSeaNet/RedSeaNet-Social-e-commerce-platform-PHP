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
use Redseanet\Customer\Model\Collection\Balance as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Bootstrap;

class BalanceController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    use \Redseanet\Admin\Traits\Stat;

    public function indexAction()
    {
        $root = $this->getLayout('admin_balance_list');
        return $root;
    }

    public function statisticsWithCustomerAction()
    {
        $root = $this->getLayout('admin_balance_statistics_customer_list');
        return $root;
    }

    public function topupTotalAction()
    {
        $collection = new Collection();
        $collection->columns(['amount' => new Expression('sum(amount)')])->where('`customer_balance`.`status`=1 and `customer_balance`.`comment`="Top Up"');

        $currency = $this->getContainer()->get('currency');
        $code = $currency->offsetGet('code');
        $getCount = function ($item) use ($code) {
            return $item->offsetGet('amount');
        };
        $result = $this->stat($collection, function ($collection) use ($getCount) {
            $result = 0;
            foreach ($collection as $item) {
                $result += $getCount($item);
            }
            return $result;
        }, $getCount, 'created_at');
        $result['amount'] = $result['amount'];
        $result['daily'] = $result['daily'];
        $result['monthly'] = $result['monthly'];
        $result['yearly'] = $result['yearly'];
        return $result;
    }

    public function withdrawTotalAction()
    {
        $collection = new Collection();
        $collection->columns(['amount' => new Expression('sum(amount)')])->where('`customer_balance`.`status`=1  and (`customer_balance`.`comment`="Withdraw Balance" or `customer_balance`.`comment`="Cancel Withdraw")');

        $currency = $this->getContainer()->get('currency');
        $code = $currency->offsetGet('code');
        $getCount = function ($item) use ($code) {
            return $item->offsetGet('amount');
        };
        $result = $this->stat($collection, function ($collection) use ($getCount) {
            $result = 0;
            foreach ($collection as $item) {
                $result += $getCount($item);
            }
            return $result;
        }, $getCount, 'created_at');
        $result['amount'] = $result['amount'];
        $result['daily'] = $result['daily'];
        $result['monthly'] = $result['monthly'];
        $result['yearly'] = $result['yearly'];
        return $result;
    }

    public function systemAdjustmentTotalAction()
    {
        $collection = new Collection();
        $collection->columns(['amount' => new Expression('sum(amount)')])->where('`customer_balance`.`status`=1  and `customer_balance`.`comment`="System Adjustment"');

        $currency = $this->getContainer()->get('currency');
        $code = $currency->offsetGet('code');
        $getCount = function ($item) use ($code) {
            return $item->offsetGet('amount');
        };
        $result = $this->stat($collection, function ($collection) use ($getCount) {
            $result = 0;
            foreach ($collection as $item) {
                $result += $getCount($item);
            }
            return $result;
        }, $getCount, 'created_at');
        $result['amount'] = $result['amount'];
        $result['daily'] = $result['daily'];
        $result['monthly'] = $result['monthly'];
        $result['yearly'] = $result['yearly'];
        return $result;
    }
}
