<?php

namespace Redseanet\Cms\Controller;

use Redseanet\Cms\Model\Collection\Page;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Model\Language;
use Laminas\Feed\Writer\Feed;

class FeedController extends ActionController
{
    public function indexAction()
    {
        $category = $this->getOption('category');
        if (!$category) {
            return $this->notFoundAction();
        }
        $cache = $this->getContainer()->get('cache');
        $data = $this->getRequest()->getQuery();
        $language = empty($data['lang']) ? Bootstrap::getLanguage() : (new Language())->load($data['lang'], 'code');
        $lang = $language->getId();
        $type = isset($data['feed']) && in_array(strtolower($data['feed']), ['rss', 'atom', 'rdf']) ? strtolower($data['feed']) : 'atom';
        $xml = $cache->fetch($type . '-' . $lang . '-' . $category->getId(), 'FEED_');
        if (!$xml) {
            $pages = new Page();
            $pages->join('cms_category_page', 'cms_page.id=cms_category_page.page_id', [], 'left')
                    ->join('cms_page_language', 'cms_page_language.page_id=cms_page.id', [], 'left')
                    ->where([
                        'cms_category_page.category_id' => $category->getId(),
                        'cms_page_language.language_id' => $lang
                    ]);
            $last = clone $pages;
            $last->order('updated_at DESC, created_at DESC')
                    ->limit(1);
            $last->load(true, true);
            $writer = new Feed();
            $writer->setTitle($category['name'][$lang]);
            $writer->setLink($this->getBaseUrl());
            $writer->setDescription($this->translate($this->getContainer()->get('config')['theme/global/default_description'], [], null, $language['code']));
            $writer->setFeedLink($this->getRequest()->getUri()->__toString(), $type);
            $writer->setDateModified(count($last) ? strtotime($last[0]['updated_at'] ?? $last[0]['created_at']) : time());
            $pages->walk(function ($page) use ($writer, $category) {
                $entry = $writer->createEntry();
                $entry->setTitle($page['title']);
                $entry->setLink($page->getUrl($category));
                $entry->setDateModified(strtotime($page['updated_at'] ?? $page['created_at']));
                $entry->setDateCreated(strtotime($page['created_at']));
                $entry->setDescription($page['description'] ?
                                htmlspecialchars(htmlspecialchars_decode($page['description'], 162), 162, 'UTF-8') :
                                mb_substr(preg_replace('/\<[^\>]+\>/', '', htmlspecialchars(htmlspecialchars_decode($page['content'], 162), 162, 'UTF-8')), 0, 50));
                $entry->setContent(htmlspecialchars(htmlspecialchars_decode($page['content'], 162), 162, 'UTF-8'));
                $writer->addEntry($entry);
            });
            $xml = $writer->export($type);
            $cache->save($type . '-' . $lang . '-' . $category->getId(), $xml, 'FEED_', 86400);
        }
        $this->getResponse()->withHeader('Content-type', 'application/xml; charset=UTF-8');
        return $xml;
    }
}
