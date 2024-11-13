<?php

namespace Redseanet\Admin\ViewModel\Sales\Customer;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Catalog\Model\Product\Option;
use Redseanet\Sales\Model\Collection\Cart as Collection;

class Cart extends Grid
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
            'base_price' => [
                'label' => 'Price'
            ],
            'qty' => [
                'label' => 'Qty',
                'handler' => function ($value) {
                    return (float) $value;
                }
            ],
            'base_total' => [
                'label' => 'Subtotal'
            ]
        ];
        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        $cart = new Collection();
        $cart->where([
            'customer_id' => $this->getVariable('model')->getId(),
            'status' => 1
        ])->order('id DESC')
                ->limit(1);
        $cart->load(true, false);
        if (count($cart)) {
            $items = $cart[0]->getItems(true);
        } else {
            $items = new Collection\Item();
            $items->where('0');
        }
        $query = $this->getQuery();
        $this->filter($items, array_intersect_key($query, ['asc' => 1, 'desc' => 1, 'limit' => 1, 'page' => 1]));
        return $items;
    }
}
