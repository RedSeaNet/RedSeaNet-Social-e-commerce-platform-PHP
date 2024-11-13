<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Eav\Grid as PGrid;
use Redseanet\Catalog\Model\Collection\Category as Collection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Category extends PGrid
{
    protected $action = [
        'getAppendAction' => 'Admin\\Catalog\\Category::edit',
        'getEditAction' => 'Admin\\Catalog\\Category::edit',
        'getDeleteAction' => 'Admin\\Catalog\\Category::delete'
    ];
    protected $translateDomain = 'catalog';
    protected $categoryTree = [];

    public function __clone()
    {
        $this->variables = [];
        $this->children = [];
    }

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_category/edit/?id=') . $item['id'] . '&pid=' .
                $item['parent_id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_category/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getAppendAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_category/edit/') . '?pid=' . $item['id'] . '" title="' . $this->translate('Append Subcategory') .
                '"><span class="fa fa-fw fa-plus" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Append') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        return parent::prepareColumns([
            'id' => [
                'label' => 'ID',
            ]
        ]);
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        if ($user->getStore()) {
            $collection->where(['store_id' => $user->getStore()->getId()]);
        }
        return $collection;
    }

    protected function prepareCategoryTree()
    {
        $collection = $this->getVariable('collection');
        if ($collection->count()) {
            foreach ($collection as $category) {
                if (!isset($this->categoryTree[(int) $category['parent_id']])) {
                    $this->categoryTree[(int) $category['parent_id']] = [];
                }
                $this->categoryTree[(int) $category['parent_id']][] = $category;
            }
            foreach ($this->categoryTree as $key => $value) {
                uasort($this->categoryTree[$key], function ($a, $b) {
                    if (!isset($a['sort_order'])) {
                        $a['sort_order'] = 0;
                    }
                    if (!isset($b['sort_order'])) {
                        $b['sort_order'] = 0;
                    }
                    return $a['sort_order'] <=> $b['sort_order'];
                });
            }
        }
    }

    public function getChildrenCategories($pid)
    {
        if (empty($this->categoryTree)) {
            $this->prepareCategoryTree();
        }
        return $this->categoryTree[$pid] ?? [];
    }

    public function renderCategory($category, $level = 1)
    {
        $child = clone $this;
        $child->setTemplate('admin/catalog/category/renderer')
                ->setVariable('category', $category)
                ->setVariable('children', $this->getChildrenCategories($category['id']))
                ->setVariable('level', $level);
        return $child;
    }
}
