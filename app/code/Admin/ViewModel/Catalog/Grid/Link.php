<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;
use Redseanet\Lib\Bootstrap;

class Link extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Catalog\\Product::edit',
        'getDeleteAction' => 'Admin\\Catalog\\Product::delete'
    ];
    protected $messAction = [
    ];
    protected $translateDomain = 'catalog';

    public function getEditAction($item)
    {
        $page = 1;
        if (!empty($this->query['page'])) {
            $page = $this->query['page'];
        }
        $linktype = 'crosssells';
        if ($item['type'] == 'r') {
            $linktype = 'related';
        } elseif ($item['type'] == 'u') {
            $linktype = 'upsells';
        }
        return '<a data-id="' . $item['id'] . '" data-title="' . $this->translate(ucfirst($linktype)) . '" data-toggle="modal" data-target="div#productLinkModal" data-href="' . $this->getAdminUrl(':ADMIN/catalog_product/list/?linktype=' . $linktype . '&store_id=&name=&sku=&uri_key=&recommended=&status=&id=' . $item['id'] . '&page=' . $page) . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product/productlinkdelete/') . '" data-method="delete" data-params="product_id=' . $item['product_id'] .
                '&linked_product_id=' . $item['linked_product_id'] . '&type=' . $item['type'] . '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $columns = [
            'id' => [
                'label' => 'Product ID',
            ],
            'name' => [
                'label' => 'Name',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => true,
                'handler' => function ($id, &$item) {
                    return $this->hasPermission('Admin\Catalog\Product::edit') ? '<a href="' . $this->getAdminUrl('catalog_product/edit/?id=' . $item['id']) . '">' . $id . '</a>' : $id;
                }
            ],
            'sku' => [
                'label' => 'SKU',
                'type' => 'text'
            ],
            'type' => [
                'label' => 'Type',
                'type' => 'select',
                'options' => [
                    'r' => 'Related Products',
                    'c' => 'Cross-sells',
                    'u' => 'Up-sells',
                ]
            ],
            'link_id' => [
                'label' => 'Linked Product ID',
                'type' => 'text'
            ],
            'link_name' => [
                'label' => 'Linked Product Name',
                'type' => 'text',
                'handler' => function ($id, &$item) {
                    return $this->hasPermission('Admin\Catalog\Product::edit') ? '<a href="' . $this->getAdminUrl('catalog_product/edit/?id=' . $item['link_id']) . '">' . $id . '</a>' : $id;
                }
            ],
            'link_sku' => [
                'label' => 'Linked Product Sku',
                'type' => 'text'
            ],
            'sort_order' => [
                'label' => 'Sort Order',
                'type' => 'text',
                'use4sort' => false,
                'use4filter' => false
            ]
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->columns(['id', 'name', 'sku']);
        $collection->join('product_link', 'product_link.product_id=main_table.id', ['product_id', 'linked_product_id', 'type', 'sort_order'], 'inner');
        $collection->join(['linked_product_index' => 'product_' . $collection->languageId . '_index'], 'linked_product_index.id=product_link.linked_product_id', ['link_id' => 'id', 'link_name' => 'name', 'link_sku' => 'sku'], 'inner');
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'product_link.sort_order';
        }
        if ($this->getQuery('id')) {
            $collection->where('main_table.id=' . $this->getQuery('id'));
        }
        if ($this->getQuery('name')) {
            $collection->where("main_table.name like '%" . $this->getQuery('name') . "%'");
        }
        if ($this->getQuery('sku')) {
            $collection->where("main_table.sku like '%" . $this->getQuery('sku') . "%'");
        }
        if ($this->getQuery('type')) {
            $collection->where("product_link.type='" . $this->getQuery('type') . "'");
        }
        if ($this->getQuery('link_id')) {
            $collection->where('product_link.linked_product_id=' . $this->getQuery('link_id'));
        }
        if ($this->getQuery('link_name')) {
            $collection->where("linked_product_index.name like '%" . $this->getQuery('link_name') . "%'");
        }
        if ($this->getQuery('link_sku')) {
            $collection->where("linked_product_index.sku like '%" . $this->getQuery('link_sku') . "%'");
        }
        //echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        return $collection;
    }
}
