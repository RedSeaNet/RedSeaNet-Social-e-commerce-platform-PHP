<?php

namespace Redseanet\Cms\ViewModel;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Template;

class Navigation extends Template
{
    protected $navigations = null;
    protected $urls = [];

    public function getCategory()
    {
        return $this->getVariable('category');
    }

    public function getNavigation()
    {
        return $this->navigations;
    }

    public function setNavigation($navigations)
    {
        $this->navigations = $navigations;
        return $this;
    }

    public function getLanguageId()
    {
        return Bootstrap::getLanguage()->getId();
    }

    public function getTopCategory($category = null)
    {
        if (is_null($category)) {
            $category = $this->getCategory();
        }
        $parent = $category->getParentCategory();
        return $parent ? $this->getTopCategory($parent) : $category;
    }
}
