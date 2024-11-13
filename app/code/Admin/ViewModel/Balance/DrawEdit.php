<?php

namespace Redseanet\Admin\ViewModel\Balance;

use Redseanet\Admin\ViewModel\Edit;
use Redseanet\Balance\Source\DrawType;

class DrawEdit extends Edit
{
    public function getSaveUrl()
    {
        return $this->getAdminUrl('customer_draw/save/');
    }

    public function getTitle()
    {
        return 'Edit Application';
    }

    protected function prepareElements($columns = [])
    {
        $model = $this->getVariable('model');
        $detail = '';
        $json = json_decode($model['account'], true);
        foreach ($json as $key => $value) {
            $detail .= $this->translate(ucwords($key)) . ': ' . $value . chr(10);
        }
        $customer = $model->getCustomer();
        $columns = [
            'id' => [
                'type' => 'hidden',
            ],
            'csrf' => [
                'type' => 'csrf'
            ],
            'customer_id' => [
                'type' => 'link',
                'label' => 'Customer',
                'link' => 'customer_manage/edit/?id=' . $model->offsetGet('customer_id'),
                'content' => $model->getCustomer()->offsetGet('username')
            ],
            'type' => [
                'type' => 'select',
                'label' => 'Account Type',
                'options' => (new DrawType())->getSourceArray(),
                'attrs' => [
                    'disabled' => 'disabled'
                ]
            ],
            'account' => [
                'type' => 'textarea',
                'label' => 'Account Detail',
                'attrs' => [
                    'disabled' => 'disabled'
                ],
                'value' => $detail
            ],
            'amount' => [
                'type' => 'price',
                'label' => 'Amount',
                'attrs' => [
                    'disabled' => 'disabled'
                ]
            ],
            'created_at' => [
                'type' => 'datetime',
                'label' => 'Applied at',
                'attrs' => [
                    'disabled' => 'disabled'
                ]
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'required' => 'required',
                'options' => [
                    '-1' => 'Canceled',
                    '0' => 'Processing',
                    '1' => 'Complete'
                ]
            ]
        ];
        return parent::prepareElements($columns);
    }
}
