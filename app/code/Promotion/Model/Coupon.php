<?php

namespace Redseanet\Promotion\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Promotion\Model\Collection\Coupon\Log as Collection;
use Redseanet\Promotion\Model\Coupon\Log as Model;
use Laminas\Db\Sql\Expression;

class Coupon extends AbstractModel
{
    protected $rule = [];

    protected function construct()
    {
        $this->init('promotion_coupon', 'id', ['id', 'code', 'promotion_id', 'status']);
    }

    public function getRule()
    {
        if (empty($this->rule) && !empty($this->storage['promotion_id'])) {
            $this->rule = new Rule();
            $this->rule->load($this->storage['promotion_id']);
        }
        return $this->rule;
    }

    public function apply($orderId, $customerId = null)
    {
        if ($this->getId()) {
            $model = new Model();
            $model->setData([
                'coupon_id' => $this->getId(),
                'order_id' => $orderId,
                'customer_id' => $customerId
            ])->save();
            if (!empty($this->getRule()['uses_per_coupon'])) {
                $collection = new Collection();
                $collection->columns(['count' => new Expression('count(1)')])
                        ->where(['coupon_id' => $this->getId()]);
                if (count($collection) && $collection[0]['count'] >= $this->getRule()['uses_per_coupon']) {
                    $this->setData('status', 0)->save();
                }
            }
        }
        return $this;
    }
}
