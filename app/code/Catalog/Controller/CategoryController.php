<?php

namespace Redseanet\Catalog\Controller;

use Redseanet\Api\Model\Collection\Rest\Attribute;
use Redseanet\Catalog\Model\Category;
use Redseanet\Catalog\Model\Collection\Category as Collection;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Model\Collection\Eav\Attribute as EAVAttr;
use Laminas\Db\Sql\Expression;
use Redseanet\Lib\Bootstrap;

class CategoryController extends ActionController
{
    use \Redseanet\Catalog\Traits\Breadcrumb;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\Filter;

    public function indexAction()
    {
        if ($this->getOption('category_id')) {
            $category = new Category();
            $category->load($this->getOption('category_id'));
            $products = $this->prepareCollection($category->getProducts(), $category);
            if ($this->getOption('is_json')) {
                $result = [];
                $columns = new Attribute();
                $columns->columns(['attributes'])
                        ->where([
                            'role_id' => 0,
                            'operation' => 1,
                            'resource' => $products::ENTITY_TYPE
                        ]);
                $columns->load(true, true);
                if (count($columns)) {
                    $columns = explode(',', $columns[0]['attributes']);
                    $columns[] = 'id';
                    $products->columns($columns);
                    $products->walk(function ($item) use (&$result, $category) {
                        $result[] = [
                            'absolute_url' => $item->getUrl($category),
                            'thumbnail_url' => $item->getThumbnail(),
                            'images_url' => $item->getImages()
                        ] + $item->toArray();
                    });
                }
                return $result;
            } else {
                $root = $this->getLayout('catalog_category');
                $root->getChild('head')->setTitle($category['meta_title'] ?: $category['name'])
                        ->setDescription($category['meta_description'])
                        ->setKeywords($category['meta_keywords']);
                $content = $root->getChild('content');
                $this->generateCrumbs($content->getChild('breadcrumb'), $this->getOption('category_id'));
                $content->getChild('toolbar')->setCategory($category)->setCollection($products);
                $content->getChild('list')->setCategory($category)->setProducts($products);
                $categoryBottom = $content->getChild('toolbar_bottom');
                $categoryBottom->setCategory($category)->setCollection($products);
                $categoryBottom->getChild('pager')->setCollection($products);
                return $root;
            }
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
            if ($condition['asc'] === 'default') {
                $select->order('product_in_category.sort_order asc');
            } else {
                $select->order((strpos($condition['asc'], ':') ?
                                str_replace(':', '.', $condition['asc']) :
                                $condition['asc']) . ' ASC');
            }
            unset($condition['asc'], $condition['desc']);
        } elseif (isset($condition['desc'])) {
            if ($condition['desc'] === 'default') {
                $select->order('product_in_category.sort_order desc');
            } else {
                $select->order((strpos($condition['desc'], ':') ?
                                str_replace(':', '.', $condition['desc']) :
                                $condition['desc']) . ' DESC');
            }
            unset($condition['desc']);
        } elseif ($category && $default = $category['default_sortable']) {
            $select->order($default === 'default' ? 'product_in_category.sort_order' : $default);
        }
        $attributes = new EAVAttr();
        $attributes->columns(['code'])
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where([
                    'eav_entity_type.code' => $collection::ENTITY_TYPE,
                    'filterable' => 1
                ]);
        $attributes->load(true, true);
        $cond = [];
        foreach ($attributes as $attribute) {
            if (!empty($condition[$attribute['code']])) {
                $cond[$attribute['code']] = $condition[$attribute['code']];
            }
        }
        $cond['status'] = 1;
        $this->filter($collection, $cond, ['limit' => 1, 'order' => 1]);
        return $collection;
    }

    public function navAction()
    {
        if ($this->getOption('is_json')) {
            $result = [];
            $columns = new Attribute();
            $columns->columns(['attributes'])
                    ->where([
                        'role_id' => 0,
                        'operation' => 1,
                        'resource' => Collection::ENTITY_TYPE
                    ]);
            $columns->load(true, true);
            if (count($columns)) {
                $columns = explode(',', $columns[0]['attributes']);
                $collection = new Collection();
                $collection->columns(array_merge(['id', 'parent_id', 'sort_order'], $columns))
                        ->where(['include_in_menu' => 1, 'parent_id' => null], 'OR')
                        ->order('sort_order ASC');
                $tree = [];
                $collection->walk(function ($item) use (&$tree) {
                    if (!isset($tree[(int) $item['parent_id']])) {
                        $tree[(int) $item['parent_id']] = [];
                    }
                    $tree[(int) $item['parent_id']][] = $item;
                });
                if (isset($tree[0])) {
                    $result = $this->generateTree(0, $tree);
                }
            }
            return $result;
        }
        return $this->notFoundAction();
    }

    protected function generateTree($pid, $tree)
    {
        $children = [];
        foreach ($tree[$pid] as $child) {
            if (isset($tree[$child['id']])) {
                $child['children_categories'] = $this->generateTree($child['id'], $tree);
            }
            $array = $child->toArray();
            if (!empty($array['image'])) {
                $array['image'] = $child->getImage();
            }
            if (!empty($array['thumbnail'])) {
                $array['thumbnail'] = $child->getThumbnail();
            }
            $children[] = $array;
        }
        return $children;
    }
}
