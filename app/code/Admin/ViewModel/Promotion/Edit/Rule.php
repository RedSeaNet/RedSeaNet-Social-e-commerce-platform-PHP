<?php

namespace Redseanet\Admin\ViewModel\Promotion\Edit;

use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Lib\Source\Store;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Rule extends Edit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('promotion/save/');
    }

    public function getDeleteUrl()
    {
        $model = $this->getVariable('model');
        if ($model && $model->getId()) {
            return $this->getAdminUrl('promotion/delete/');
        }
        return false;
    }

    public function getTitle()
    {
        return $this->getQuery('id') ? 'Edit Promotion Activities' : 'Add New Promotion Activities';
    }

    protected function prepareElements($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'name' => [
                'type' => 'text',
                'label' => 'Label',
                'required' => 'required',
                'comment' => 'Set coupon identification label.'
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Condition',
                'reqiored' => 'required',
                'comment' => 'Used to describe current conditions and the scope of the use of coupons, such as: full 100 minus 30, clothing products available.'
            ],
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden'
            ] : [
                'type' => 'checkbox',
                'options' => (new Store())->getSourceArray(),
                'label' => 'AvailableIn',
                'comment' => 'Please select the range to use coupon.'
            ]),
            'from_date' => [
                'type' => 'datetime',
                'label' => 'From Date',
                'attrs' => [
                    'data-toggle' => 'datetimepicker'
                ]
            ],
            'to_date' => [
                'type' => 'datetime',
                'label' => 'To Date',
                'attrs' => [
                    'data-toggle' => 'datetimepicker'
                ]
            ],
            'use_coupon' => [
                'type' => 'hidden',
                'required' => 'required',
                'value' => 1
            ],
            'uses_per_coupon' => [
                'type' => 'tel',
                'label' => 'Uses per Coupon',
                'required' => 'required',
                'default' => 1,
                'comment' => 'eg. In 3, the whole site only the top 3 users can use this coupon, or fill in the blank 0 unlimited use.'
            ],
            'uses_per_customer' => [
                'type' => 'tel',
                'label' => 'Uses per Customer',
                'required' => 'required',
                'default' => 1,
                'comment' => 'eg. In 3, each user can use coupon 3 times, or 0 blank fill said does not limit the use number of times.'
            ],
            'sort_order' => [
                'type' => 'tel',
                'label' => 'Priority',
                'required' => 'required',
                'comment' => 'Set priority associated with promotion,smaller the number,higher the priority.'
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ],
                'required' => 'required'
            ]
        ];
        return parent::prepareElements($columns);
    }
}
