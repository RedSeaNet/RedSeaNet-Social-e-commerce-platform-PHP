<?php

namespace Redseanet\Bargain\Model;

use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Increment;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class BargainCaseHelp extends AbstractModel
{
    protected function construct()
    {
        $this->init('bargain_case_help', 'id', ['id', 'customer_id', 'bargain_id', 'bargain_case_id', 'price', 'type']);
    }
}
