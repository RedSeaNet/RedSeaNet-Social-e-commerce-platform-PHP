<?php

namespace Redseanet\Admin\ViewModel\Bargain\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Bargain\Model\Collection\BargainCase as Collection;
use Redseanet\Admin\Model\User;

class BargainCase extends Grid
{
    protected $translateDomain = 'bargain';
    protected $action = [
        'getHelpAction' => 'Admin\\bargain::bargainCaseHelpList',
        'getDeleteAction' => 'Admin\\bargain::delete'
    ];

    public function getHelpAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/bargain/bargaincasehelplist/?bargain_case_id=' . $item['id']) . '" title="' . $this->translate('Bargin Case Help') .
                '"><span class="fa fa-fw fa-list" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Bargin Case Help') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/bargain/deletecase/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
                'use4sort' => true,
                'use4filter' => false
            ],
            'bargain_id' => [
                'label' => 'Bargain ID',
                'use4sort' => true,
                'use4filter' => true
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'use4sort' => true,
                'use4filter' => true,
                'handler' => function ($id) {
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=' . $id) . '">' . $id . '</a>' : $id;
                }
            ],
            'username' => [
                'label' => 'Username',
                'use4sort' => true,
                'use4filter' => true
            ],
            'bargain_price_min' => [
                'label' => 'Min Price',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'bargain_price' => [
                'type' => 'text',
                'label' => 'Price',
                'use4filter' => false,
                'use4sort' => true,
            ],
            'price' => [
                'type' => 'text',
                'label' => 'Price',
                'use4filter' => false,
                'use4sort' => true,
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
            ],
            'mini_program_qr' => [
                'type' => 'text',
                'label' => 'Mini Program QR',
                'use4sort' => false,
                'use4filter' => false
            ],
            'web_qr' => [
                'type' => 'text',
                'label' => 'Web QR',
                'use4sort' => false,
                'use4filter' => false
            ],
            'mp_qr' => [
                'type' => 'text',
                'label' => 'Mq QR',
                'use4sort' => false,
                'use4filter' => false
            ],
            'created_at' => [
                'label' => 'Created at',
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $collection = new Collection();
        $collection->join('customer_1_index', 'customer_1_index.id=bargain_case.customer_id', ['username'], 'left');
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'bargain_case.created_at';
        }
        return parent::prepareCollection($collection);
    }
}
