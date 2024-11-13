<?php

namespace Redseanet\Cms\Controller;

use Redseanet\Lib\Controller\ActionController;

class PageController extends ActionController
{
    use \Redseanet\Lib\Traits\Filter;

    public function indexAction()
    {
        $page = $this->getOption('page');
        $category = $this->getOption('category');
        if ($this->getOption('isJson')) {
            if ($page) {
                return $page->toArray();
            } else {
                $pages = $category->getPages();
                $pages->columns(['id', 'title', 'description', 'uri_key', 'keywords', 'created_at', 'updated_at'])
                        ->join(['tr' => 'resource'], 'tr.id=thumbnail', ['thumbnail' => 'real_name'], 'left')
                        ->join(['ir' => 'resource'], 'ir.id=image', ['image' => 'real_name'], 'left')
                        ->where(['status' => 1]);
                $this->filter($pages, $this->getRequest()->getQuery());
                return $pages->load(true, true)->toArray();
            }
        } elseif (!$page) {
            return $this->notFoundAction();
        }
        $layout = $this->getContainer()->get('layout');

        $root = $layout->getLayout('page-' . $page['uri_key'], true) ?:
                $layout->getLayout($category && $category->offsetGet('show_navigation') ? 'cms_page_with_nav' : 'cms_page', true);
        $root->addBodyClass('page-' . $page['uri_key']);
        $head = $root->getChild('head');
        $head->setTitle($page['title'])
                ->setKeywords($page['keywords'])
                ->setDescription($page['description'])
                ->addOgMeta('og:title', $page['title'])
                ->addOgMeta('og:description', $page['description'])
                ->addOgMeta('og:type', 'article')
                ->addOgMeta('og:url', $page->getUrl())
                ->addOgMeta('og:image', $this->getPubUrl('frontend/images/logo.png'));
        $navigation = $root->getChild('navigation', true);
        if ($navigation) {
            $navigation->setVariables([
                'page' => $page,
                'category' => $category
            ]);
        }
        $root->getChild('page', true)->setPageModel($page);
        return $root;
    }
}
