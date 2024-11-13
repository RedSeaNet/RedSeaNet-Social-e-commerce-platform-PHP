<?php

namespace Redseanet\Admin\ViewModel\Balance;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Balance\Source\DrawType;
use Redseanet\Customer\Model\Collection\Balance\Draw as Collection;

class DrawGrid extends Grid
{
    protected $action = [
        'getEditAction' => 'Admin\\Customer\\Draw::edit'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_draw/edit/?id=') . $item['id'] .
                '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'type' => [
                'type' => 'select',
                'label' => 'Account Type',
                'options' => (new DrawType())->getSourceArray()
            ],
            'account' => [
                'label' => 'Account Detail',
                'use4filter' => false,
                'use4sort' => false,
                'handler' => function ($json) {
                    $json = json_decode($json, true);
                    $result = '';
                    foreach ($json as $key => $value) {
                        $result .= $this->translate(ucwords($key)) . ': ' . $value . '<br />';
                    }
                    return $result;
                }
            ],
            'amount' => [
                'type' => 'price',
                'label' => 'Amount'
            ],
            'created_at' => [
                'type' => 'daterange',
                'label' => 'Applied at'
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    '-1' => 'Canceled',
                    '0' => 'Processing',
                    '1' => 'Complete'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();

        return $collection;
    }
}
