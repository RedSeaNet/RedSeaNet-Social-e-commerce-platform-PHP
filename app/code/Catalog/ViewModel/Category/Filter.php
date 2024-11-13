<?php

namespace Redseanet\Catalog\ViewModel\Category;

use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Model\Collection\Eav\Attribute;

class Filter extends Toolbar
{
    protected $showCount = false;

    public function showCount($flag = null)
    {
        if (is_bool($flag)) {
            $this->showCount = $flag;
        }
        return $this->showCount;
    }

    public function getCurrentFilters()
    {
        $query = $this->getQuery();
        return array_diff_key($query, ['desc' => 1, 'asc' => 1, 'page' => 1, 'limit' => 1, 'q' => 1]);
    }

    public function getFilters()
    {
        $result = [];
        if ($this->getCollection()->count()) {
            if ($this->getVariable('category')) {
                $ids = [];
                foreach ($this->getVariable('category')->getChildrenCategories() as $category) {
                    $ids[] = $category['id'];
                }
            }
            $attributes = new Attribute();
            $attributes->withLabel()
                    ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                    ->where(['filterable' => 1, 'eav_entity_type.code' => Product::ENTITY_TYPE]);
            foreach ($this->getCollection() as $product) {
                if (!empty($ids)) {
                    foreach ($product->getCategories() as $category) {
                        if (in_array($category['id'], $ids)) {
                            if (!isset($result['category'])) {
                                $result['category'] = [
                                    'label' => $this->translate('Category'),
                                    'values' => []
                                ];
                            }
                            if (!isset($result['category']['values'][$category['id']])) {
                                $result['category']['values'][$category['id']] = ['label' => $category['name'], 'count' => 1];
                            } else {
                                $result['category']['values'][$category['id']]['count']++;
                            }
                        }
                    }
                }
                foreach ($attributes as $attribute) {
                    if ($product[$attribute['code']] !== '') {
                        if (!isset($result[$attribute['code']])) {
                            $result[$attribute['code']] = [
                                'label' => $attribute['label'],
                                'values' => []
                            ];
                        }
                        $this->statAttributeValue($result[$attribute['code']]['values'], $product[$attribute['code']], in_array($attribute['input'], ['select', 'radio', 'checkbox', 'multiselect']) ? $attribute : false);
                    }
                }
            }
        }
        return $result;
    }

    protected function statAttributeValue(&$array, $value, $attribute = false)
    {
        if (is_string($value) && strpos($value, ',')) {
            $value = explode(',', $value);
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!isset($array[$v])) {
                    $array[$v] = [
                        'label' => $attribute ? $attribute->getOption($v) : $v,
                        'count' => 1
                    ];
                } else {
                    $array[$v]['count']++;
                }
            }
        } elseif (!is_null($value)) {
            if (!isset($array[$value])) {
                $array[$value] = [
                    'label' => $attribute ? $attribute->getOption($value) : $value,
                    'count' => 1
                ];
            } else {
                $array[$value]['count']++;
            }
        }
    }

    public function getFilterUrl($key, $value)
    {
        $query = $this->getRequest()->getQuery();
        $query[$key] = $value;
        return $this->getCurrentUri()->withQuery(http_build_query($query));
    }

    public function getInitFilterUrl($key = null)
    {
        $query = $this->getRequest()->getQuery();
        if (is_null($key)) {
            return $this->getCurrentUri()->withQuery(http_build_query(array_intersect_key($query, ['desc' => 1, 'asc' => 1, 'page' => 1, 'limit' => 1])));
        } else {
            unset($query[$key]);
        }
        return $this->getCurrentUri()->withQuery(http_build_query($query));
    }
}
