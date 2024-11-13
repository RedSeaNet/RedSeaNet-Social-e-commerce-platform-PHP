<?php

namespace Redseanet\Forum\Traits;

use Redseanet\Forum\Model\Category;
use Redseanet\Forum\Model\Post;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Breadcrumb as ViewModel;

trait Breadcrumb
{
    /**
     * @param ViewModel $breadcrumb
     * @param Category $category
     */
    protected function generateCrumbs(ViewModel $breadcrumb, $category)
    {
        $languageId = Bootstrap::getLanguage()->getId();
        $this->addCrumb($breadcrumb, $category, $languageId);
    }

    protected function addCrumb(ViewModel $breadcrumb, $category, $languageId = null)
    {
        if (is_null($languageId)) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        if (isset($category['parent_id']) && $category['parent_id']) {
            $parent = new Category();
            $parent->load($category['parent_id']);
            $this->addCrumb($breadcrumb, $parent, $languageId);
            $url = $category->getUrl();
            if (!empty($url)) {
                $breadcrumb->addCrumb([
                    'link' => $url,
                    'label' => (!empty($category['name'][$languageId]) ? $category['name'][$languageId] : '')
                ]);
            }
        }
    }
}
