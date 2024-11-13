<?php

namespace Redseanet\Catalog\ViewModel\Category;

use Redseanet\Catalog\Source\Sortable;

class SortBy extends Toolbar
{
    protected $attributes = null;

    public function getAttributes()
    {
        if (is_null($this->attributes)) {
            $this->attributes = (new Sortable())->getSourceArray();
        }
        return $this->attributes;
    }

    public function getSorters()
    {
        $result = [];
        $category = $this->getVariable('category');
        if ($category && $category['sortable']) {
            foreach ((array) $category['sortable'] as $key) {
                $result[$key] = $this->getAttributes()[$key];
            }
        }
        return $result;
    }

    public function getCurrentSorter()
    {
        $query = $this->getQuery();
        return $query['asc'] ?? ($query['desc'] ?? ($this->getVariable('category') ?
                $this->getVariable('category')['default_sortable'] : ''));
    }

    public function isAscending()
    {
        return !$this->getQuery('desc');
    }

    public function getSorterUrl($key, $value = null)
    {
        $query = $this->getRequest()->getQuery();
        if (is_null($value)) {
            if (isset($query[$this->isAscending() ? 'asc' : 'desc']) && $key === $query[$this->isAscending() ? 'asc' : 'desc']) {
                $query[$this->isAscending() ? 'desc' : 'asc'] = $key;
                unset($query[$this->isAscending() ? 'asc' : 'desc']);
            } else {
                $query['asc'] = $key;
                unset($query['desc']);
            }
        } else {
            $query[$key] = $value;
        }
        return $this->getCurrentUri()->withQuery(http_build_query($query));
    }
}
