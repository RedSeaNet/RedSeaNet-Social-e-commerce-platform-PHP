<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Edit\Product;

use Redseanet\Catalog\Model\Collection\Category as Collection;

class Category extends Tab
{
    protected static $categories = null;
    protected static $activeIds = null;

    public function getCategories()
    {
        if (is_null(self::$categories)) {
            $collection = new Collection();
            $collection->order('parent_id ASC, sort_order DESC');
            self::$categories = [];
            foreach ($collection as $item) {
                $pid = (int) $item['parent_id'];
                if (!isset(self::$categories[$pid])) {
                    self::$categories[$pid] = [];
                }
                self::$categories[$pid][] = $item;
            }
        }
        return self::$categories;
    }

    public function getActiveIds()
    {
        if (is_null(self::$activeIds)) {
            $collection = $this->getProduct()->getCategories();
            self::$activeIds = [];
            if (count($collection)) {
                foreach ($collection->toArray() as $item) {
                    self::$activeIds[] = $item['id'];
                }
            }
        }
        return self::$activeIds;
    }

    public function renderCategory($level = 0)
    {
        if (!empty($this->getCategories()[$level])) {
            foreach ($this->getCategories()[$level] as $category) {
                $child = new static();
                $child->setTemplate('retailer/catalog/product/category/item')
                        ->setVariable('category', $category);
                echo $child->__toString();
            }
        }
    }
}
