<?php

namespace Redseanet\Admin\ViewModel\Bulk\Grid;

use Redseanet\Admin\ViewModel\Eav\Grid as PGrid;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Store;
use Redseanet\Bulk\Model\Collection\Bulk as Collection;
use Redseanet\Bulk\Model\Collection\Bulk\Item as bulkItem;
use Redseanet\Bulk\Model\Collection\Bulk\Member as bulkMember;

class Bulking extends PGrid
{
    protected $translateDomain = 'bulk';
    protected $action = [
        'getDeleteAction' => 'Admin\\Bulk::delete'
    ];
    protected $messAction = [
        'getMessDeleteAction' => 'Admin\\Bulk::delete'
    ];

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/bulk/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getMessDeleteAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/bulk/delete/') . '" data-method="delete" data-serialize=".grid .table" title="' . $this->translate('Delete') .
                '"><span>' . $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        return [
            'id' => [
                'type' => 'hidden',
                'label' => 'ID',
                'handler' => function ($id, &$item) {
                    $itemObject = new bulkItem();
                    $itemObject->where(['bulk_id' => $id]);
                    $itemObject->load(true, true);
                    if (count($itemObject) > 0) {
                        $html = '';
                        for ($i = 0; $i < count($itemObject); $i++) {
                            if ($itemObject[$i]['product_id'] != '') {
                                $html .= '<p><a href="' . $this->getAdminUrl(':ADMIN/catalog_product/edit/?id=' . $itemObject[$i]['product_id']) . '">' . $itemObject[$i]['product_name'] . '</a></p>';
                            } else {
                                $html .= '<p>' . $itemObject[$i]['product_name'] . '</p>';
                            }
                        }
                        $item['product'] = $html;
                    } else {
                        $item['product'] = '';
                    }

                    $memberObject = new bulkMember();
                    $memberObject->where(['bulk_id' => $id]);
                    $memberObject->load(true, true);
                    if (count($memberObject) > 0) {
                        $html = '';
                        for ($i = 0; $i < count($memberObject); $i++) {
                            $html .= '<p><a href="' . $this->getAdminUrl(':ADMIN/sales_order/view/?id=' . $memberObject[$i]['order_id']) . '">' . $memberObject[$i]['order_id'] . '</a></p>';
                        }
                        $item['order'] = $html;
                    } else {
                        $item['order'] = '';
                    }
                    return $id;
                }
            ],
            'customer_id' => [
                'label' => 'Customer ID',
                'use4filter' => false,
                'use4sort' => false,
                'handler' => function ($id) {
                    return '<a href="' . $this->getAdminUrl('customer_manage/edit/?id=' . $id) . '">' . $id . '</a>';
                }
            ],
            'customer_name' => [
                'type' => 'text',
                'label' => 'Customer',
            ],
            'size' => [
                'type' => 'number',
                'label' => 'Size',
                'use4filter' => false
            ],
            'count' => [
                'type' => 'number',
                'label' => 'Count',
                'use4filter' => false,
            ],
            'status' => [
                'type' => 'select',
                'label' => 'Status',
                'options' => [
                    '0' => 'Disable',
                    '1' => 'Enable'
                ]
            ],
            'description' => [
                'label' => 'Description',
                'type' => 'text',
                'use4filter' => false,
                'use4sort' => false
            ],
            'product' => [
                'label' => 'Product',
                'type' => 'text',
                'use4filter' => false,
                'use4sort' => false
            ],
            'order' => [
                'label' => 'Order',
                'type' => 'text',
                'use4filter' => false,
                'use4sort' => false
            ],
            'created_at' => [
                'type' => 'daterange',
                'label' => 'Created at',
                'use4filter' => true,
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ]
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection($collection);
    }
}
