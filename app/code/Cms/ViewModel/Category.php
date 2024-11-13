<?php

namespace Redseanet\Cms\ViewModel;

use Redseanet\Cms\Model\Category as Model;
use Redseanet\Lib\ViewModel\Template;

class Category extends Template
{
    protected $category = null;

    public function __construct()
    {
        $this->setTemplate('cms/category');
    }

    public function getCategory()
    {
        if (is_null($this->category)) {
            $this->category = new Model();
            $this->category->load($this->getVariable('id'));
        }
        return $this->category;
    }

    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    public function getPages()
    {
        if ($pages = $this->getCategory()->getPages()) {
            $pages->order('created_at DESC');
            if ($limit = (int) $this->getVariable('limit', false)) {
                $pages->limit($limit);
            }
        }
        return $pages ?: [];
    }
}
