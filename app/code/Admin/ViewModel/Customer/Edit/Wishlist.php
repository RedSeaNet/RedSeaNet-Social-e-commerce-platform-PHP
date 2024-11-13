<?php

namespace Redseanet\Admin\ViewModel\Customer\Edit;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Catalog\Model\Product\Option;
use Redseanet\Customer\Model\Collection\Wishlist\Item as Collection;

class Wishlist extends Grid
{
    use \Redseanet\Lib\Traits\Filter;

    protected function prepareColumns()
    {
        $columns = [
            'id' => [
                'label' => 'ID'
            ],
            'product_id' => [
                'label' => 'Product Name',
                'handler' => function ($id, $item) {
                    return $this->hasPermission('Admin\Catalog\Product::edit') ? '<a href="' . $this->getAdminUrl('catalog_product/edit/?id=') . $id . '">' . $item['product_name'] . '</a>' : $item['product_name'];
                }
            ],
            'options' => [
                'label' => 'Options',
                'handler' => function ($value) {
                    $result = '';
                    $options = json_decode($value, true);
                    foreach ($options as $key => $value) {
                        $option = new Option();
                        $option->load($key);
                        $result .= $option->getLabel() . ':' . $option->getValue($value) . '<br />';
                    }
                    return $result;
                }
            ],
            'qty' => [
                'label' => 'Qty',
                'handler' => function ($value) {
                    return (float) $value;
                }
            ],
            'price' => [
                'label' => 'Price'
            ]
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        $items = new Collection();
        $items->join('wishlist', 'wishlist.id=wishlist_item.wishlist_id', [], 'left')
                ->where([
                    'wishlist.customer_id' => $this->getVariable('model')->getId()
                ]);
        $query = $this->getQuery();
        $this->filter($items, array_intersect_key($query, ['asc' => 1, 'desc' => 1, 'limit' => 1, 'page' => 1]));
        return $items;
    }
}
