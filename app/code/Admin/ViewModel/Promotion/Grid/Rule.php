<?php

namespace Redseanet\Admin\ViewModel\Promotion\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Promotion\Model\Collection\Rule as Collection;
use Redseanet\Admin\Model\User;

class Rule extends Grid
{
    protected $translateDomain = 'promotion';
    protected $action = [
        'getEditAction' => 'Admin\\Promotion::edit',
        'getEnableAction' => 'Admin\\Promotion::enable',
        'getDisableAction' => 'Admin\\Promotion::disable',
        'getDeleteAction' => 'Admin\\Promotion::delete'
    ];

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/promotion/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/promotion/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getEnableAction($item)
    {
        return $item['status'] ? false : ('<a href="' . $this->getAdminUrl(':ADMIN/promotion/enable/') . '?id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Enable') .
                '"><span class="fa fa-fw fa-play" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Enable') . '</span></a>');
    }

    public function getDisableAction($item)
    {
        return $item['status'] ? ('<a href="' . $this->getAdminUrl(':ADMIN/promotion/disable/') . '?id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Disable') .
                '"><span class="fa fa-fw fa-pause" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Disable') . '</span></a>') : false;
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'name' => [
                'label' => 'Name',
                'class' => 'text-left'
            ],
            'use_coupon' => [
                'label' => 'Use Coupon',
                'type' => 'select',
                'options' => [
                    'No',
                    'Yes'
                ]
            ],
            'from_date' => [
                'type' => 'datetime',
                'label' => 'From Date'
            ],
            'to_date' => [
                'type' => 'datetime',
                'label' => 'To Date'
            ],
            'sort_order' => [
                'type' => 'tel',
                'label' => 'Priority'
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
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
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }
}
