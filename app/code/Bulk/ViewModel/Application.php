<?php

namespace Redseanet\Bulk\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Bulk\Model\Collection\Bulk;

class Application extends Template
{
    public function getBulks($activeOnly = false)
    {
        $collection = new Bulk();
        $collection->join('bulk_sale_item', 'bulk_sale_item.bulk_id=bulk_sale.id', [], 'left')
                ->where(['bulk_sale_item.product_id' => $this->getVariable('product')->getId()])
                ->order('created_at DESC');
        if ($activeOnly) {
            $collection->where(['bulk_sale.status' => 1]);
            if ($this->getConfig()['catalog/bulk_sale/limitation']) {
                $collection->getSelect()->where->lessThan('count', 'size', 'identifier', 'identifier');
            }
        }
        return $collection;
    }
}
