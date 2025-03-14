<?php

namespace Redseanet\Customer\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Lib\Bootstrap;
use Redseanet\Promotion\Model\Collection\Rule;
use Redseanet\Customer\Model\Customer;

class Coupon extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;

    /**
     * @param int $id
     * @param string $token
     * @param int $customerId
     * @param int $page
     * @param int $limit
     * @param int $languageId
     * @return array
     */
    public function getCouponList($id, $token, $customerId, $conditionData = [], $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }

        $collection = new Rule();
        $collection->withStore(true)
                ->where(['use_coupon' => 1]);

        if (!isset($conditionData['limit']) || $conditionData['limit'] == '') {
            $conditionData['limit'] = 20;
        } else {
            $conditionData['limit'] = intval($conditionData['limit']);
        }
        if (!isset($conditionData['page']) || $conditionData['page'] == '') {
            $conditionData['page'] = 1;
        } else {
            $conditionData['page'] = intval($conditionData['page']);
        }
        $total = $collection->count();
        $last_page = ceil($total / $conditionData['limit']);
        $resultData['pagination'] = [
            "total" => $total,
            "per_page" => $conditionData['limit'],
            "current_page" => $conditionData['page'],
            "last_page" => $last_page,
            "next_page" => ($last_page > $conditionData['page'] ? $conditionData['page'] + 1 : $last_page),
            "previous_page" => ($conditionData['page'] > 1 ? $conditionData['page'] - 1 : 1),
            "has_next_page" => ($last_page > $conditionData['page'] ? true : false),
            "has_previous_page" => ($conditionData['page'] > 1 && $last_page > 1 ? true : false)
        ];
        if ($conditionData['page'] > 1) {
            $collection->order('id DESC')->limit($conditionData['limit'])->offset((int) ($conditionData['page'] - 1) * $conditionData['limit']);
        } else {
            $collection->order('id DESC')->limit($conditionData['limit'])->offset(0);
        }
        $coupons = [];
        foreach ($collection as $rule) {
            if ($this->match($rule->getCondition(), true, $customerId)) {
                $coupons[] = $rule->toArray();
            }
        }
        $resultData["coupons"] = $coupons;
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get coupon list successfully'];
        return $this->responseData;
    }

    public function match($condition, $default, $customerId) {
        if ($condition['identifier'] === 'customer_id') {
            return $condition['operator'] === '=' ? $customerId == $condition['value'] : $customerId != $condition['value'];
        } elseif ($condition['identifier'] === 'customer_group') {
            $customer = new Customer();
            $customer->load($customerId);
            foreach ($customer->getGroup() as $group) {
                if ($condition['operator'] === '=' && $group['id'] == $condition['value'] || $condition['operator'] !== '=' && $group['id'] != $condition['value']) {
                    return true;
                }
            }
            return false;
        } elseif ($condition['identifier'] === 'customer_level') {
            $customer = new Customer();
            $customer->load($customerId);
            return $condition['operator'] === '=' ? $customer->getLevel() == $condition['value'] : $customer->getLevel() != $condition['value'];
        } elseif ($condition['identifier'] === 'combination') {
            $result = $condition['operator'] === 'and' ? 1 : 0;
            foreach ($condition->getChildren() as $child) {
                if ($condition['operator'] === 'and') {
                    $result &= (int) $this->match($child, $condition['value'], $customerId);
                    if (!$result) {
                        break;
                    }
                } else {
                    $result |= (int) $this->match($child, $condition['value'], $customerId);
                    if ($result) {
                        break;
                    }
                }
            }
            return $result === (int) $condition['value'];
        } else {
            return $default;
        }
    }

}
