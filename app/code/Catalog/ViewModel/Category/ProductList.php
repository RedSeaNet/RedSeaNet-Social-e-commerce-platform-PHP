<?php

namespace Redseanet\Catalog\ViewModel\Category;

use Redseanet\Catalog\Model\Category;
use Redseanet\Catalog\ViewModel\Product\Price;
use Redseanet\Lib\ViewModel\Template;

class ProductList extends Template
{
    protected $products = null;
    protected $urls = [];
    protected $indexer = null;

    public function getTemplate()
    {
        if (!$this->template) {
            return 'catalog/product/list/' . $this->getQuery('mode', 'grid');
        }
        return parent::getTemplate();
    }

    public function getCategory()
    {
        return $this->getVariable('category', null);
    }

    public function setCategory(Category $category)
    {
        $this->variables['category'] = $category;
        return $this;
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function setProducts($products)
    {
        if ($limit = (int) $this->getVariable('limit', false)) {
            $products->limit($limit);
        }
        $this->products = $products;
        return $this;
    }

    public function getPriceBox($product)
    {
        $box = new Price();
        $box->setVariable('product', $product);
        return $box;
    }
}
