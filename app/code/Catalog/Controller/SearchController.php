<?php

namespace Redseanet\Catalog\Controller;

use Exception;
use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Catalog\Model\Collection\SearchTerm as Terms;
use Redseanet\Catalog\Model\SearchTerm;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Search\Model\Factory;
use Laminas\Db\Sql\Predicate\In;

class SearchController extends CategoryController
{
    public function indexAction()
    {
        $config = $this->getContainer()->get('config');
        $interval = (int) $config['catalog/product/search_interval'];

        $segment = new Segment('core');
        if ($interval && time() - $segment->get('lastSearch', 0) < $interval) {
            $segment->addMessage([['message' => $this->translate('The administrator limits the search interval to %ds, please try it later.', [$interval]), 'level' => 'danger']]);
            return $this->redirectReferer();
        }
        $frequency = !(new Segment('customer'))->get('hasLoggedIn') && $config['catalog/product/search_frequency'] !== '';
        if ($frequency && $segment->get('search_frequency', 0) >= (int) $config['catalog/product/search_frequency']) {
            return $this->redirect('customer/account/login/?success_url=' . str_replace(['+', '/', '='], ['-', '_', ''], urlencode($this->getRequest()->getUri()->__toString())));
        }
        $data = $this->getRequest()->getQuery();
        $root = $this->getLayout('catalog_category');
        $content = $root->getChild('content');

        $languageId = Bootstrap::getLanguage()->getId();
        if (isset($data['q'])) {
            $engine = (new Factory())->getSearchEngineHandler();
            $result = $engine->select('catalog_search', $data, $languageId);
            $ids = [];
            foreach ($result as $item) {
                $ids[$item['id']] = $item['weight'] ?? 0;
            }
            $this->saveTerm($data, $ids);
            $crumb = $this->translate('Search Result');
            $root->getChild('head')->setTitle($crumb)
                    ->setKeywords(str_replace(' ', ',', $data['q']));
            $content->getChild('breadcrumb')->addCrumb(['label' => $crumb]);
        }
        $products = new Product($languageId);
        $products->where(empty($ids) ? '0' : new In('id', array_keys($ids)));
        $products = $this->prepareCollection($products);
        $content->getChild('toolbar')->setCollection($products);
        $content->getChild('list')->setProducts($products);
        $content->getChild('toolbar_bottom')->setCollection($products);
        $segment->set('lastSearch', time());
        if ($frequency) {
            $segment->set('search_frequency', $segment->get('search_frequency', 0) + 1);
        }
        return $root;
    }

    protected function saveTerm($data, $ids)
    {
        try {
            $term = new SearchTerm();
            $term->load($data['q']);
            if ($term->getId()) {
                $term->setData('popularity', (int) $term->offsetGet('popularity') + 1);
            } else {
                $term->setData([
                    'term' => $data['q'],
                    'count' => count($ids),
                    'store_id' => $data['store_id'] ?? null,
                    'category_id' => $data['category_id'] ?? null,
                    'status' => count($ids) ? 1 : 0
                ]);
            }
            $term->save();
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
        }
    }

    public function termAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $data = $this->getRequest()->getQuery();
            $collection = new Terms();
            $select = $collection->columns(['term', 'count'])
                            ->order('popularity DESC')
                            ->limit(10)
                            ->where(['status' => 1])
                    ->where->greaterThan('count', 0);
            if (!empty($data['q'])) {
                $select->where->like('term', '%' . trim($data['q']) . '%');
            }
            if (!empty($data['store_id'])) {
                $select->where(['store_id' => $data['store_id']]);
            }
            if (!empty($data['category_id'])) {
                $select->where(['category_id' => $data['category_id']]);
            }
            $collection->load(true, true);
            return json_encode($collection->toArray());
        }
        return $this->notFoundAction();
    }

    public function nameidAction()
    {
        $config = $this->getContainer()->get('config');
        $interval = (int) $config['catalog/product/search_interval'];

        $segment = new Segment('core');
        if ($interval && time() - $segment->get('lastSearch', 0) < $interval) {
            $segment->addMessage([['message' => $this->translate('The administrator limits the search interval to %ds, please try it later.', [$interval]), 'level' => 'danger']]);
            return $this->redirectReferer();
        }
        $frequency = !(new Segment('customer'))->get('hasLoggedIn') && $config['catalog/product/search_frequency'] !== '';
        if ($frequency && $segment->get('search_frequency', 0) >= (int) $config['catalog/product/search_frequency']) {
            return $this->redirect('customer/account/login/?success_url=' . str_replace(['+', '/', '='], ['-', '_', ''], urlencode($this->getRequest()->getUri()->__toString())));
        }
        $data = $this->getRequest()->getPost();
        $languageId = Bootstrap::getLanguage()->getId();
        $products = new Product($languageId);
        if (isset($data['q'])) {
            $ids = [];
            $engine = (new Factory())->getSearchEngineHandler();
            $result = $engine->select('catalog_search', $data, $languageId);

            foreach ($result as $item) {
                $ids[$item['id']] = $item['weight'] ?? 0;
            }
            $this->saveTerm($data, $ids);
            $products->where(empty($ids) ? '0' : new In('id', array_keys($ids)));
        }
        $products = $this->prepareCollection($products);
        $segment->set('lastSearch', time());
        if ($frequency) {
            $segment->set('search_frequency', $segment->get('search_frequency', 0) + 1);
        }
        $resultData = [];
        $resultData['total_count'] = count($products);
        $this->filter($products);
        $resultData['results'] = [];
        $resultData['pagination']['more'] = false;
        if (count($products) > 0) {
            for ($c = 0; $c < count($products); $c++) {
                $resultData['results'][] = ['id' => $products[$c]['id'], 'text' => $products[$c]['name']];
            }
        }
        return json_encode($resultData);
    }
}
