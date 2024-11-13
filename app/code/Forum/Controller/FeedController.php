<?php

namespace Redseanet\Forum\Controller;

use Redseanet\Forum\Model\Collection\Post;
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
        $post = $this->getOption('post');
        $product = $this->getOption('product');
        return $post ? $this->feedOnAuthor($post) :
                ($product ? $this->feedOnProduct($product) : $this->feedOnCategory($category));
    }

    private function feedOnProduct($product)
    {
        $cache = $this->getContainer()->get('cache');
        $data = $this->getRequest()->getQuery();
        $language = empty($data['lang']) ? Bootstrap::getLanguage() : (new Language())->load($data['lang'], 'code');
        $lang = $language->getId();
        $type = isset($data['feed']) && in_array(strtolower($data['feed']), ['rss', 'atom', 'rdf']) ? strtolower($data['feed']) : 'atom';
        $xml = $cache->fetch($type . '-' . $lang . '-forum-p' . $product->getId(), 'FEED_');
        if (!$xml) {
            $posts = new Post();
            $posts->where(['language_id' => $lang, 'product_id' => $product->getId()]);
            $last = clone $posts;
            $last->order('updated_at DESC, created_at DESC')
                    ->limit(1);
            $last->load(true, true);
            $writer = new Feed();
            $writer->setTitle($product['name']);
            $writer->setLink($this->getBaseUrl());
            $writer->setDescription($this->translate($this->getContainer()->get('config')['theme/global/default_description'], [], null, $language['code']));
            $writer->setFeedLink($this->getRequest()->getUri()->__toString(), $type);
            $writer->setDateModified(count($last) ? strtotime($last[0]['updated_at'] ?? $last[0]['created_at']) : time());
            $posts->walk(function ($page) use ($writer) {
                $entry = $writer->createEntry();
                $entry->setTitle($page['title']);
                $entry->setLink($page->getUrl());
                $entry->setDateModified(strtotime($page['updated_at'] ?? $page['created_at']));
                $entry->setDateCreated(strtotime($page['created_at']));
                $entry->setDescription($page['description'] ?
                                htmlspecialchars(htmlspecialchars_decode($page['description'], 162), 162, 'UTF-8') :
                                mb_substr(preg_replace('/\<[^\>]+\>/', '', htmlspecialchars(htmlspecialchars_decode($page['content'], 162), 162, 'UTF-8')), 0, 50));
                $entry->setContent(htmlspecialchars(htmlspecialchars_decode($page['content'], 162), 162, 'UTF-8'));
                $writer->addEntry($entry);
            });
            $xml = $writer->export($type);
            $cache->save($type . '-' . $lang . '-forum-p' . $post['customer_id'], $xml, 'FEED_', 86400);
        }
        $this->getResponse()->withHeader('Content-type', 'application/xml; charset=UTF-8');
        return $xml;
    }

    private function feedOnAuthor($post)
    {
        $cache = $this->getContainer()->get('cache');
        $data = $this->getRequest()->getQuery();
        $language = empty($data['lang']) ? Bootstrap::getLanguage() : (new Language())->load($data['lang'], 'code');
        $lang = $language->getId();
        $type = isset($data['feed']) && in_array(strtolower($data['feed']), ['rss', 'atom', 'rdf']) ? strtolower($data['feed']) : 'atom';
        $xml = $cache->fetch($type . '-' . $lang . '-forum-c' . $post['customer_id'], 'FEED_');
        if (!$xml) {
            $posts = new Post();
            $posts->where(['language_id' => $lang, 'customer_id' => $post['customer_id']]);
            $last = clone $posts;
            $last->order('updated_at DESC, created_at DESC')
                    ->limit(1);
            $last->load(true, true);
            $writer = new Feed();
            $writer->setTitle($post->getCustomer()['username']);
            $writer->setLink($this->getBaseUrl());
            $writer->setDescription($this->translate($this->getContainer()->get('config')['theme/global/default_description'], [], null, $language['code']));
            $writer->setFeedLink($this->getRequest()->getUri()->__toString(), $type);
            $writer->setDateModified(count($last) ? strtotime($last[0]['updated_at'] ?? $last[0]['created_at']) : time());
            $posts->walk(function ($page) use ($writer) {
                $entry = $writer->createEntry();
                $entry->setTitle($page['title']);
                $entry->setLink($page->getUrl());
                $entry->setDateModified(strtotime($page['updated_at'] ?? $page['created_at']));
                $entry->setDateCreated(strtotime($page['created_at']));
                $entry->setDescription($page['description'] ?
                                htmlspecialchars(htmlspecialchars_decode($page['description'], 162), 162, 'UTF-8') :
                                mb_substr(preg_replace('/\<[^\>]+\>/', '', htmlspecialchars(htmlspecialchars_decode($page['content'], 162), 162, 'UTF-8')), 0, 50));
                $entry->setContent(htmlspecialchars(htmlspecialchars_decode($page['content'], 162), 162, 'UTF-8'));
                $writer->addEntry($entry);
            });
            $xml = $writer->export($type);
            $cache->save($type . '-' . $lang . '-forum-c' . $post['customer_id'], $xml, 'FEED_', 86400);
        }
        $this->getResponse()->withHeader('Content-type', 'application/xml; charset=UTF-8');
        return $xml;
    }

    private function feedOnCategory($category)
    {
        $cache = $this->getContainer()->get('cache');
        $data = $this->getRequest()->getQuery();
        $language = empty($data['lang']) ? Bootstrap::getLanguage() : (new Language())->load($data['lang'], 'code');
        $lang = $language->getId();
        $type = isset($data['feed']) && in_array(strtolower($data['feed']), ['rss', 'atom', 'rdf']) ? strtolower($data['feed']) : 'atom';
        $xml = $cache->fetch($type . '-' . $lang . '-forum-' . $category->getId(), 'FEED_');
        if (!$xml) {
            $posts = $category->getPosts();
            $posts->where(['language_id' => $lang]);
            $last = clone $posts;
            $last->order('updated_at DESC, created_at DESC')
                    ->limit(1);
            $last->load(true, true);
            $writer = new Feed();
            $writer->setTitle($category['name'][$lang]);
            $writer->setLink($this->getBaseUrl());
            $writer->setDescription($this->translate($this->getContainer()->get('config')['theme/global/default_description'], [], null, $language['code']));
            $writer->setFeedLink($this->getRequest()->getUri()->__toString(), $type);
            $writer->setDateModified(count($last) ? strtotime($last[0]['updated_at'] ?? $last[0]['created_at']) : time());
            $posts->walk(function ($page) use ($writer) {
                $entry = $writer->createEntry();
                $entry->setTitle($page['title']);
                $entry->setLink($page->getUrl());
                $entry->setDateModified(strtotime($page['updated_at'] ?? $page['created_at']));
                $entry->setDateCreated(strtotime($page['created_at']));
                $entry->setDescription($page['description'] ?
                                htmlspecialchars(htmlspecialchars_decode($page['description'], 162), 162, 'UTF-8') :
                                mb_substr(preg_replace('/\<[^\>]+\>/', '', htmlspecialchars(htmlspecialchars_decode($page['content'], 162), 162, 'UTF-8')), 0, 50));
                $entry->setContent(htmlspecialchars(htmlspecialchars_decode($page['content'], 162), 162, 'UTF-8'));
                $writer->addEntry($entry);
            });
            $xml = $writer->export($type);
            $cache->save($type . '-' . $lang . '-forum-' . $category->getId(), $xml, 'FEED_', 86400);
        }
        $this->getResponse()->withHeader('Content-type', 'application/xml; charset=UTF-8');
        return $xml;
    }
}
