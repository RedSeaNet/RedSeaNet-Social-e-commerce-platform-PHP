<?php

namespace Redseanet\Admin\ViewModel\Retailer;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Retailer\Model\Collection\Application as Collection;

class Grid extends PGrid
{
    protected $editUrl = '';
    protected $translateDomain = 'retailer';
    protected $action = [
        'getEditAction' => 'Admin\\Retailer\\Application::edit',
        'getDeleteAction' => 'Admin\\Retailer\\Application::delete'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Retailer\\Application::delete'
    ];

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/retailer_apply/delete/') . '" data-method="delete" data-params="id=' . $item['customer_id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/retailer_apply/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    public function getRowLink($item)
    {
        return $this->getAdminUrl(':ADMIN/retailer_apply/edit/?id=' . $item->getId());
    }

    public function getEditAction($item)
    {
        $page = 1;
        if (!empty($this->query['page'])) {
            $page = $this->query['page'];
        }
        return '<a href="' . $this->getAdminUrl(':ADMIN/retailer_apply/edit/?id=' . $item['customer_id'] . '&page=' . $page) . '"title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Edit') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'customer_id' => [
                'label' => 'Customer ID',
                'use4sort' => false
            ],
            'phone' => [
                'label' => 'Phone Number',
                'use4sort' => false
            ],
            'brand_type' => [
                'label' => 'Brand',
                'use4sort' => false,
                'type' => 'select',
                'options' => [
                    'Agency',
                    'Own'
                ]
            ],
            'product_type' => [
                'label' => 'Product Type',
                'use4sort' => false
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'use4sort' => false,
                'options' => [
                    'Disabled',
                    'Enabled'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->order('status ASC, created_at DESC');
        return parent::prepareCollection($collection);
    }
}
