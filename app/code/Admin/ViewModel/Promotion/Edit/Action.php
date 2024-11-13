<?php

namespace Redseanet\Admin\ViewModel\Promotion\Edit;

use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Promotion\Model\Collection\Handler as Collection;

class Action extends Edit
{
    public function getOptions($source)
    {
        if (is_subclass_of($source, '\\Redseanet\\Lib\\Source\\SourceInterface')) {
            return (new $source())->getSourceArray();
        }
        return [];
    }

    public function getCollection()
    {
        if ($this->getQuery('id')) {
            $collection = new Collection();
            $collection->where(['promotion_id' => $this->getQuery('id')]);
            $result = [];
            foreach ($collection as $item) {
                if (!isset($result[(int) $item->offsetGet('parent_id')])) {
                    $result[(int) $item->offsetGet('parent_id')] = [];
                }
                $result[(int) $item->offsetGet('parent_id')][] = $item->toArray();
            }
            return $result;
        }
        return [];
    }

    protected function prepareElements($columns = [])
    {
        $columns = [
            'apply_to' => [
                'type' => 'select',
                'label' => 'Apply to',
                'required' => 'required',
                'options' => ['Include Freight and Taxes', 'Only Amount of Subtotals']
            ],
            'is_fixed' => [
                'type' => 'select',
                'label' => 'Discount Calculation Method',
                'required' => 'required',
                'options' => [1 => 'Reduce the amount of fixed', 0 => 'Price is calculated in percentage']
            ],
            'price' => [
                'type' => 'price',
                'label' => 'Discount Intensity',
                'required' => 'required'
            ],
            'free_shipping' => [
                'type' => 'select',
                'label' => 'Free Shipping',
                'required' => 'required',
                'options' => ['No', 'Yes'],
                'comment' => 'Set coupon on the basis of whether price concessions to avoid shipping discount, amount set to 0 after the opening of item can generate an independent free shipping coupons.'
            ],
            'stop_processing' => [
                'type' => 'select',
                'label' => 'Stop low Priority',
                'required' => 'required',
                'options' => ['No', 'Yes'],
                'comment' => 'This option is, then the priority is less than this privilege can not be used again, the same priority is to give up a random preference, open this feature, please set the priority of the coupon.'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
