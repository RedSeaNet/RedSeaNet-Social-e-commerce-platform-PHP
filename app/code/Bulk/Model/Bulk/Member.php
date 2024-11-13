<?php

namespace Redseanet\Bulk\Model\Bulk;

use Redseanet\Lib\Model\AbstractModel;

class Member extends AbstractModel
{
    protected function construct()
    {
        $this->init('bulk_sale_member', 'id', [
            'bulk_id', 'member_id', 'order_id'
        ]);
    }
}
