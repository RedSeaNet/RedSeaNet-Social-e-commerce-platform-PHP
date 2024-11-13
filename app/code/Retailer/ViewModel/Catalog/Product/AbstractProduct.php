<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Product;

use Redseanet\Catalog\Source\Category as CatalogCategory;
use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Retailer\Source\Category as RetailerCategory;
use Redseanet\Retailer\ViewModel\AbstractViewModel;
use Laminas\Db\Sql\Select;

abstract class AbstractProduct extends AbstractViewModel implements ProductInterface
{
    use \Redseanet\Lib\Traits\Filter {
        filter as traitFilter;
    }

    protected static $currency = null;
    protected $actions = [];
    protected $messActions = [];

    public function __construct()
    {
        $this->setTemplate('retailer/catalog/product/list');
    }

    public function getActions($item = null)
    {
        foreach ($this->actions as $action) {
            if (is_callable([$this, $action])) {
                yield $this->$action($item);
            }
        }
    }

    public function getMessActions($item = null)
    {
        foreach ($this->messActions as $action) {
            if (is_callable([$this, $action])) {
                yield $this->$action($item);
            }
        }
    }

    protected function filter($select)
    {
        if ($select instanceof AbstractCollection) {
            $select = $select->getSelect();
        }
        $data = $this->getQuery();
        if (!empty($data['category'])) {
            $subSelect = new Select('product_in_category');
            $subSelect->columns(['product_id'])
                    ->where(['category_id' => $data['category']]);
            $select->where->in('id', $subSelect);
        }
        if (!empty($data['store_category'])) {
            $subSelect = new Select('retailer_category_with_product');
            $subSelect->columns(['product_id'])
                    ->where(['category_id' => $data['store_category']]);
            $select->where->in('id', $subSelect);
        }
        if (!empty($data['price']) && count($data['price']) == 2 && !empty($data['price'][0]) && !empty($data['price'][1])) {
            $select->where->greaterThanOrEqualTo('price', $data['price'][0])
                    ->lessThanOrEqualTo('price', $data['price'][1]);
        }
        if (!empty($data['name'])) {
            $data['name'] = '%' . $data['name'] . '%';
        }
        unset($data['category'], $data['store_category'], $data['price'], $data['store_id']);
        $this->traitFilter($select, $data);
    }

    public function getFilters()
    {
        $data = $this->getQuery();
        return [
            'page' => [
                'type' => 'hidden',
                'value' => $data['page'] ?? 1
            ],
            'name' => [
                'type' => 'text',
                'label' => 'Product Name',
                'value' => $data['name'] ?? ''
            ],
            'sku' => [
                'type' => 'text',
                'label' => 'SKU',
                'value' => $data['sku'] ?? ''
            ],
            'price[]' => [
                'type' => 'pricerange',
                'label' => 'Regular Price',
                'value' => $data['price'] ?? []
            ],
            'category' => [
                'type' => 'select',
                'label' => 'System Category',
                'options' => (new CatalogCategory())->getSourceArray(false),
                'value' => $data['category'] ?? ''
            ],
            'store_category' => [
                'type' => 'select',
                'label' => 'Store Category',
                'options' => (new RetailerCategory())->getSourceArray($this->getRetailer()['store_id']),
                'value' => $data['store_category'] ?? ''
            ],
            'recommended' => [
                'type' => 'bool',
                'label' => 'Recommended',
                'value' => $data['recommended'] ?? ''
            ]
        ];
    }

    public function getInputBox($key, $item)
    {
        if (empty($item['type'])) {
            return '';
        }
        $class = empty($item['view_model']) ? '\\Redseanet\\Lib\\ViewModel\\Template' : $item['view_model'];
        $box = new $class();
        $box->setVariables([
            'key' => $key,
            'item' => $item,
            'parent' => $this
        ]);
        $box->setTemplate('page/renderer/' . (in_array($item['type'], ['multiselect', 'checkbox']) ? 'select' : $item['type']), false);
        return $box;
    }

    public function renderItem($product)
    {
        $item = new static();
        $item->setTemplate('retailer/catalog/product/item');
        $item->setVariable('product', $product);
        return $item;
    }

    public function getCurrency()
    {
        if (is_null(self::$currency)) {
            self::$currency = $this->getContainer()->get('currency');
        }
        return self::$currency;
    }
}
