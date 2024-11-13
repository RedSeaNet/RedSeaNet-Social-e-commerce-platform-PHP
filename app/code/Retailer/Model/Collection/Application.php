<?php

namespace Redseanet\Retailer\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Application extends AbstractCollection
{
    protected function construct()
    {
        $this->init('retailer_application');
    }

    protected function afterLoad(&$result)
    {
        foreach ($result as $key => $item) {
            $result[$key]['id'] = (isset($item['customer_id']) ? $item['customer_id'] : '');
        }
        parent::afterLoad($result);
    }
}
