<?php

namespace Redseanet\Admin\ViewModel\Forum\Grid;

use Redseanet\Admin\ViewModel\Grid;
use Redseanet\Forum\Model\Collection\Category as Collection;

class Category extends Grid
{
    protected $action = [
        'getAppendAction' => 'Admin\\Forum\\Category::edit',
        'getEditAction' => 'Admin\\Forum\\Category::edit',
        'getDeleteAction' => 'Admin\\Forum\\Category::delete'
    ];
    protected $translateDomain = 'forum';
    protected $categoryTree = [];

    public function __clone()
    {
        $this->variables = [];
        $this->children = [];
    }

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_category/edit/?id=') . $item['id'] . '&pid=' .
                $item['parent_id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_category/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getAppendAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/forum_category/edit/') . '?pid=' . $item['id'] . '" title="' . $this->translate('Append Subcategory') .
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
        $collection->withName();
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
