<?php

namespace Redseanet\Promotion\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Store;
use Redseanet\Promotion\Model\Collection\Coupon as CouponCollection;
use Redseanet\Promotion\Model\Collection\Condition as ConditionCollection;
use Redseanet\Promotion\Model\Collection\Handler as HandlerCollection;
use Laminas\Db\Sql\Expression;

class Rule extends AbstractModel
{
    protected $condition = null;

    protected function construct()
    {
        $this->init('promotion', 'id', [
            'id', 'name', 'description', 'status', 'use_coupon', 'uses_per_coupon', 'uses_per_customer',
            'from_date', 'to_date', 'stop_processing', 'qty', 'price', 'is_fixed',
            'per_item', 'free_shipping', 'apply_to', 'sort_order'
        ]);
    }

    public function getCoupon()
    {
        if ($this->getId() && !empty($this->storage['use_coupon'])) {
            $collection = new CouponCollection();
            $collection->where(['promotion_id' => $this->getId()]);
            return $collection;
        }
        return [];
    }

    public function matchCoupon($coupon, $model)
    {
        if ($coupon && is_string($coupon) && $coupons = $this->getCoupon()) {
            $coupons->join('promotion_coupon_log', 'promotion_coupon_log.coupon_id=promotion_coupon.id', ['customer_id', 'uses' => new Expression('count(promotion_coupon_log.id)')], 'left')
                    ->where([
                        'code' => $coupon,
                        'status' => 1
                    ])->columns(['code'])
                    ->group(['promotion_coupon.code', 'customer_id']);
            $coupons->load(false, true);
            if (count($coupons)) {
                $count = [];
                foreach ($coupons as $item) {
                    if ($item['customer_id'] == $model['customer_id'] && !empty($this->storage['uses_per_customer']) && $this->storage['uses_per_customer'] <= $coupons['uses']) {
                        return false;
                    }
                    if (!isset($count[$item['code']])) {
                        $count[$item['code']] = $coupons['uses'];
                    } else {
                        $count[$item['code']] += $coupons['uses'];
                    }
                }
                if (!empty($this->storage['uses_per_coupon']) && $this->storage['uses_per_coupon'] <= $count[$coupon]) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    public function getCondition()
    {
        if (is_null($this->condition) && $this->getId()) {
            $collection = new ConditionCollection();
            $collection->where([
                'promotion_id' => $this->getId(),
                'parent_id' => null
            ]);
            if (count($collection)) {
                $this->condition = $collection[0];
            }
        }
        return $this->condition;
    }

    public function getHandler()
    {
        if ($this->getId()) {
            $collection = new HandlerCollection();
            $collection->where([
                'promotion_id' => $this->getId(),
                'parent_id' => null
            ]);
            return $collection->load()[0];
        }
        return null;
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if (!empty($this->storage['coupon'])) {
            foreach ($this->storage['coupon']['code'] as $key => $code) {
                $coupon = new Coupon();
                $coupon->load($code, 'code');
                if (!$coupon->getId()) {
                    $coupon->setData([
                        'id' => null,
                        'promotion_id' => $this->getId(),
                        'code' => $code,
                        'uses_per_coupon' => $this->storage['coupon']['uses_per_coupon'][$key] ?: 0,
                        'uses_per_customer' => $this->storage['coupon']['uses_per_customer'][$key] ?: 0,
                        'status' => 1
                    ])->save();
                }
            }
        }
        if (isset($this->storage['condition'])) {
            $tableGateway = $this->getTableGateway('promotion_condition');
            $tableGateway->delete(['promotion_id' => $this->getId()]);
            $pids = [];
            foreach ($this->prepareTree($this->storage['condition']) as $id => $data) {
                $pid = $pids[$data['pid']] ?? null;
                $model = new Condition();
                $model->setData(['parent_id' => $pid, 'promotion_id' => $this->getId()] + $data)->save();
                $pids[$id] = $model->getId();
            }
        }
        if (isset($this->storage['handler'])) {
            $tableGateway = $this->getTableGateway('promotion_handler');
            $tableGateway->delete(['promotion_id' => $this->getId()]);
            $pids = [];
            foreach ($this->prepareTree($this->storage['handler']) as $id => $data) {
                $pid = $pids[$data['pid']] ?? null;
                $model = new Handler();
                $model->setData(['parent_id' => $pid, 'promotion_id' => $this->getId()] + $data)->save();
                $pids[$id] = $model->getId();
            }
        }
        if (isset($this->storage['store_id'])) {
            $tableGateway = $this->getTableGateway('promotion_in_store');
            $tableGateway->delete(['promotion_id' => $this->getId()]);
            foreach ((array) $this->storage['store_id'] as $id) {
                $this->applyToStore($id);
            }
        }
        parent::afterSave();
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('promotion_in_store', 'promotion_in_store.promotion_id=promotion.id', ['store_id'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0])) {
            $store = [];
            foreach ($result as $item) {
                if (!empty($item['store_id'])) {
                    $store[] = $item['store_id'];
                }
            }
            $result[0]['store_id'] = $store;
        }
        parent::afterLoad($result);
    }

    public function getStores()
    {
        $result = [];
        foreach ((array) $this->storage['store_id'] as $store) {
            $result[] = (new Store())->load($store);
        }
        return $result;
    }

    public function applyToStore($store)
    {
        $tableGateway = $this->getTableGateway('promotion_in_store');
        $tableGateway->insert([
            'promotion_id' => $this->getId(),
            'store_id' => is_scalar($store) ? $store : $store['id']
        ]);
        return $this;
    }

    private function prepareTree($tree)
    {
        $array = [];
        if (count($tree['identifier']) > 1) {
            foreach ($tree['identifier'] as $key => $identifier) {
                $array[$key] = [
                    'pid' => $tree['pid'][$key] ?? null,
                    'identifier' => $identifier,
                    'operator' => $tree['operator'][$key],
                    'value' => isset($tree['value'][$key]) && $tree['value'][$key] !== '' ? $tree['value'][$key] : null
                ];
            }
            uasort($array, function ($a, $b) {
                return $a['pid'] <=> $b['pid'];
            });
        }
        return $array;
    }
}
