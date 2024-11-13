<?php

namespace Redseanet\Retailer\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;

class ViewController extends ActionController
{
    use \Redseanet\Lib\Traits\Filter;

    public function indexAction()
    {
        $retailer = $this->getOption('retailer');
        $root = $this->getLayout('retailer_store_view');
        $root->getChild('head')->setTitle($retailer->getStore()->offsetGet('name'))
                ->setDescription($retailer['description'])
                ->setKeywords($retailer['keywords']);
        $root->getChild('main', true)->setVariable('store_id', $this->getOption('store_id'))
                ->setVariable('retailer', $retailer)
                ->setVariable('key', $retailer);
        $segment = new Segment('core');
        $segment->set('store', $retailer->getStore()->offsetGet('code'));
        return $root;
    }

    public function categoryAction()
    {
        $category = $this->getOption('category');
        if ($category) {
            $root = $this->getLayout('catalog_category');
            $root->getChild('head')->setTitle($category['meta_title'] ?: $category['name'])
                    ->setDescription($category['meta_description'])
                    ->setKeywords($category['meta_keywords']);
            $content = $root->getChild('content');
            $products = $this->prepareCollection($category->getProducts(), $category);
            $content->getChild('toolbar')->setCollection($products);
            $content->getChild('list')->setProducts($products);
            $content->getChild('toolbar_bottom')->setCollection($products);
            $segment = new Segment('core');
            $segment->set('store', $category->getStore()->offsetGet('code'));
            return $root;
        }
        return $this->notFoundAction();
    }

    protected function prepareCollection($collection, $category = null)
    {
        if (!is_callable([$collection, 'getSelect'])) {
            return $collection;
        }
        $condition = $this->getRequest()->getQuery();
        $config = $this->getContainer()->get('config');
        $mode = $condition['mode'] ?? 'grid';
        unset($condition['q'], $condition['type'], $condition['mode']);
        $select = $collection->getSelect();
        if ($category && isset($condition['category'])) {
            $tableGateway = $this->getTableGateway('product_in_category');
            $tmpselect = $tableGateway->getSql()->select();
            $tmpselect->columns(['product_id', 'count' => new Expression('count(category_id)')])
                    ->where(['category_id' => $condition['category']], 'OR')
                    ->where(['category_id' => $category['id']], 'OR')
                    ->group(['product_id'])
                    ->having('count>1');
            $set = $tableGateway->selectWith($tmpselect);
            $ids = [];
            foreach ($set as $row) {
                $ids[$row['product_id']] = 1;
            }
            $select->where->in('id', array_keys($ids));
            unset($condition['category']);
        }
        if (isset($condition['limit']) && $condition['limit'] === 'all' && $config['catalog/frontend/allowed_all_products']) {
            $select->reset('limit')->reset('offset');
        } else {
            $limit = isset($condition['limit']) && in_array($condition['limit'], explode(',', trim($config['catalog/frontend/allowed_per_page_' . $mode], ','))) ?
                    $condition['limit'] : $config['catalog/frontend/default_per_page_' . $mode];
            if (isset($condition['page'])) {
                $select->offset(($condition['page'] - 1) * $limit);
                unset($condition['page']);
            }
            $select->limit((int) $limit);
        }
        unset($condition['limit']);
        if (isset($condition['asc'])) {
            $select->order((strpos($condition['asc'], ':') ?
                            str_replace(':', '.', $condition['asc']) :
                            $condition['asc']) . ' ASC');
            unset($condition['asc'], $condition['desc']);
        } elseif (isset($condition['desc'])) {
            $select->order((strpos($condition['desc'], ':') ?
                            str_replace(':', '.', $condition['desc']) :
                            $condition['desc']) . ' DESC');
            unset($condition['desc']);
        } elseif ($category && $default = $category['default_sortable']) {
            $select->order($default);
        }
        $this->filter($collection, $condition, ['limit' => 1, 'order' => 1], function ($select, &$condition) {
            $condition['status'] = 1;
        });
        return $collection;
    }
}
