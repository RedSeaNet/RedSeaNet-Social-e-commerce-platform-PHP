<?php

namespace Redseanet\Admin\ViewModel\Bargain\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Bargain\Model\Collection\Bargain as Collection;
use Redseanet\Admin\Model\User;

class Bargain extends Grid
{
    protected $translateDomain = 'bargain';
    protected $action = [
        'getCaseAction' => 'Admin\\bargain::bargainCaseList',
        'getEditAction' => 'Admin\\bargain::edit',
        'getDeleteAction' => 'Admin\\bargain::delete'
    ];

    public function getCaseAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/bargain/bargaincaselist/?bargain_id=' . $item['id']) . '" title="' . $this->translate('Bargain Case') .
                '"><span class="fa fa-fw fa-list" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Bargain Case') . '</span></a>';
    }

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/bargain/edit/?id=') . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/bargain/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'id' => [
                'label' => 'ID',
            ],
            'name' => [
                'label' => 'Name',
                'class' => 'text-left',
                'use4sort' => false,
                'use4filter' => false
            ],
            'price' => [
                'label' => 'Price',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ],
            'start_time' => [
                'type' => 'datetime',
                'label' => 'Start Time',
                'use4filter' => false,
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
            'stop_time' => [
                'type' => 'datetime',
                'label' => 'End Time',
                'use4filter' => false,
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ],
            'product_id' => [
                'type' => 'tel',
                'label' => 'Product ID',
                'handler' => function ($id) {
                    $html = "<a href='" . $this->getAdminUrl('catalog_product/edit/?id=' . $id) . "'>" . $id . '</a>';
                    return $html;
                }
            ],
            'sort_order' => [
                'type' => 'tel',
                'label' => 'Sort Order'
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
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
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }
}
