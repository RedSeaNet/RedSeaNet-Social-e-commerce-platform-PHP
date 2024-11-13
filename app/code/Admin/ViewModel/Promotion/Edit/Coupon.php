<?php

namespace Redseanet\Admin\ViewModel\Promotion\Edit;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Promotion\Model\Collection\Coupon as Collection;
use Laminas\Db\Sql\Expression;

class Coupon extends Template
{
    public function getCoupons()
    {
        if ($id = $this->getQuery('id')) {
            $collection = new Collection();
            $collection->join('promotion_coupon_log', 'promotion_coupon_log.coupon_id=promotion_coupon.id', ['uses' => new Expression('count(promotion_coupon_log.id)')], 'left')
                    ->where(['promotion_id' => $id])
                    ->order('status DESC')
                    ->group('promotion_coupon.id');
            return $collection;
        }
        return [];
    }
}
