<?php

namespace Redseanet\Catalog\ViewModel\Category;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Catalog\Model\Category;
use Redseanet\Catalog\Model\Collection\Product;

class Toolbar extends Template
{
    protected $collection = null;
    protected $uri = null;

    public function getCategory()
    {
        return $this->getVariable('category');
    }

    public function setCategory($category)
    {
        $this->variables['category'] = $category;
        return $this;
    }

    /**
     * @return Product
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param Product $collection
     * @return Toolbar
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
        return $this;
    }

    public function getCurrentUri()
    {
        if (is_null($this->uri)) {
            $this->uri = $this->getRequest()->getUri();
        }
        return $this->uri;
    }
}
