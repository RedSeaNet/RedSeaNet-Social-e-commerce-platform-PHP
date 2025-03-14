<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Catalog\Model\Collection\Product\Review as Collection;
use Redseanet\Catalog\Model\Product;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Source\Language;

class Review extends PGrid {

    protected $action = [
        'getEditAction' => 'Admin\\Catalog\\Product\\Review::edit',
        'getDeleteAction' => 'Admin\\Catalog\\Product\\Review::delete'
    ];
    protected $translateDomain = 'review';

    public function getEditAction($item) {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product_review/edit/?id=') . $item['id'] . '"title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">'
                . $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item) {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product_review/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = []) {
        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID'
            ],
            'product_id' => [
                'label' => 'Product',
                'handler' => function ($id) {
                    $model = new Product();
                    $model->load($id);
                    return $this->hasPermission('Admin\Catalog\Product::edit') ? '<a href="' . $this->getAdminUrl('catalog_product/edit/?id=') . $id . '">' . $model->offsetGet('name') . '</a>' : $model->offsetGet('name');
                }
            ],
            'customer_id' => [
                'label' => 'Customer',
                'handler' => function ($id) {
                    $model = new Customer();
                    $model->load($id);
                    return $this->hasPermission('Admin\Customer\Manage::edit') ? '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=') . $id . '">' . $model->offsetGet('username') . '</a>' : $model->offsetGet('username');
                }
            ],
            'language_id' => [
                'type' => 'select',
                'label' => 'Language',
                'options' => (new Language())->getSourceArray()
            ],
            'order_id' => [
                'type' => 'text',
                'label' => 'Order',
                'handler' => function ($id) {
                    return $this->hasPermission('Admin\Sales\Order::view') ? '<a href="' . $this->getAdminUrl('sales_order/view/?id=') . $id . '">' . $id . '</a>' : $id;
                }
            ],
            'subject' => [
                'type' => 'text',
                'label' => 'Subject'
            ],
            'content' => [
                'type' => 'text',
                'label' => 'Content',
                'use4sort' => false,
                'use4filter' => false
            ],
            'reply' => [
                'type' => 'text',
                'label' => 'Reply',
                'use4sort' => false,
                'use4filter' => false
            ],
            'status' => [
                'label' => 'Status',
                'type' => 'select',
                'options' => [
                    1 => 'Enabled',
                    0 => 'Disabled'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null) {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }

}
