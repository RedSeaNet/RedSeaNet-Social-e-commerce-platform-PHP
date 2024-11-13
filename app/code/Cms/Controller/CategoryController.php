<?php

namespace Redseanet\Cms\Controller;

use Redseanet\Cms\Model\Collection\Page;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Model\Language;
use Redseanet\Cms\Model\Collection\Category;

class CategoryController extends ActionController
{
    public function indexAction()
    {
        $category = $this->getOption('category');
        if (!$category) {
            return $this->notFoundAction();
        }
        $data = $this->getRequest()->getQuery();
        $language = empty($data['lang']) ? Bootstrap::getLanguage() : (new Language())->load($data['lang'], 'code');
        $languageId = $language->getId();
        $layout = $this->getContainer()->get('layout');
        $root = $layout->getLayout('cms_page_category', true);
        $content = $root->getChild('content', true);
        $pages = new Page();
        $pages->join('cms_category_page', 'cms_page.id=cms_category_page.page_id', [], 'left')
                ->join('cms_page_language', 'cms_page.id=cms_page_language.page_id', [], 'left');
        $pages->where(['cms_page.status' => 1, 'cms_category_page.category_id' => $category->getId(), 'cms_page_language.language_id' => $languageId]);
        $category['pages'] = $pages;
        $content->setVariables([
            'category' => $category,
            'languageId' => $languageId
        ]);
        $subCategories = [];
        $subCategory = new Category();
        $subCategory->where(['status' => 1, 'parent_id' => $category->getId(), 'cms_category.show_navigation' => 1]);
        if (count($subCategory) > 0) {
            foreach ($subCategory as $sub) {
                $subPages = new Page();
                $subPages->join('cms_category_page', 'cms_page.id=cms_category_page.page_id', [], 'left')
                        ->join('cms_page_language', 'cms_page.id=cms_page_language.page_id', [], 'left');
                $subPages->where(['cms_page.status' => 1, 'cms_category_page.category_id' => $sub->getId(), 'cms_page_language.language_id' => $languageId]);
                $sub['pages'] = $subPages;
                $subCategories[] = $sub;
            }
        }
        $content->setVariables([
            'subCategories' => $subCategories
        ]);
        return $root;
    }
}
