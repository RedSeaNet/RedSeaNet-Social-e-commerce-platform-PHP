<?php

namespace Redseanet\Retailer\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Retailer\Model\Collection\Application as Collection;

class Application extends AbstractModel
{
    protected function construct()
    {
        $this->init('retailer_application', 'customer_id', ['customer_id', 'lisence_1', 'lisence_2', 'phone', 'brand_type', 'product_type', 'status']);
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        if (parent::isUpdate($constraint, $insertForce)) {
            $collection = new Collection();
            $collection->where(['customer_id' => $this->storage['customer_id']]);
            return (bool) $collection->count();
        }
        return false;
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['customer_id'])) {
            $result[0]['id'] = $result[0]['customer_id'];
        }
        parent::afterLoad($result);
    }
}
