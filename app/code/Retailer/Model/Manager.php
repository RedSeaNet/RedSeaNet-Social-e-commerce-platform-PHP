<?php

namespace Redseanet\Retailer\Model;

use Redseanet\Lib\Model\AbstractModel;

class Manager extends AbstractModel
{
    protected function construct()
    {
        $this->init('retailer_manager', 'id', ['id', 'customer_id', 'retailer_id']);
    }
}
