<?php

namespace Redseanet\Lib\Traits;

use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Eav\Collection;

/**
 * Filter Collection
 */
trait Filter
{
    /**
     * @param \Laminas\Db\Sql\Select|AbstractCollection $select
     * @param array $condition
     * @param array $skip
     * @param callable $extra
     */
    protected function filter($collection, $condition = [], $skip = [], $extra = null)
    {
        $select = $collection instanceof AbstractCollection ? $collection->getSelect() : $collection;
        if (!isset($skip['limit'])) {
            if (isset($condition['limit']) && $condition['limit'] === 'all') {
                $select->reset('limit')->reset('offset');
            } else {
                $limit = !empty($condition['limit']) ? $condition['limit'] : 30;
                if (isset($condition['page'])) {
                    $select->offset(($condition['page'] > 0 ? (intval($condition['page']) - 1) : 0) * $limit);
                    unset($condition['page']);
                }
                $select->limit((int) $limit);
            }
            if (isset($condition['limit'])) {
                unset($condition['limit']);
            }
        }
        if (!isset($skip['order'])) {
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
            }
        }
        if (is_array($condition) && !empty($condition)) {
            foreach ($condition as $key => $value) {
                if (isset($this->bannedFields) && in_array($key, $this->bannedFields) ||
                        is_scalar($value) && trim($value) === '' ||
                        is_array($value) && empty($value)) {
                    unset($condition[$key]);
                } elseif (strpos($key, ':')) {
                    if (!$this->handleFilter($select, str_replace(':', '.', $key), $value)) {
                        $condition[str_replace(':', '.', $key)] = $value;
                    }
                    unset($condition[$key]);
                } elseif ($this->handleFilter($select, $key, $value)) {
                    unset($condition[$key]);
                } elseif ($collection instanceof Collection) {
                    $attribute = new Attribute();
                    $attribute->load(addslashes($key), 'code');
                    if (in_array($attribute->offsetGet('input'), ['checkbox', 'multiselect'])) {
                        foreach ((array) $value as $v) {
                            $select->where('(' . addslashes($key) . ' LIKE \'' . addslashes($v) . ',%\' OR '
                                    . addslashes($key) . ' LIKE \'%,' . addslashes($v) . '\' OR '
                                    . addslashes($key) . ' LIKE \'%,' . addslashes($v) . ',%\' OR '
                                    . addslashes($key) . ' = \'' . addslashes($v) . '\')');
                        }
                        unset($condition[$key]);
                    }
                } else {
                    $condition[$key] = $value;
                }
            }
            if (is_callable($extra)) {
                $extra($select, $condition);
            }
            $select->where($condition);
        }
    }

    private function handleFilter($select, $key, $value)
    {
        $result = false;
        if (is_array($value)) {
            if (!empty($value['gt'])) {
                $select->where->greaterThan(addslashes($key), addslashes($value['gt']));
            }
            if (!empty($value['lt'])) {
                $select->where->lessThan(addslashes($key), addslashes($value['lt']));
            }
            if (!empty($value['gte'])) {
                $select->where->greaterThanOrEqualTo(addslashes($key), addslashes($value['gte']));
            }
            if (!empty($value['lte'])) {
                $select->where->lessThanOrEqualTo(addslashes($key), addslashes($value['lte']));
            }
            if (!empty($value['like'])) {
                $select->where->like(addslashes($key), '%' . addslashes($value['like']) . '%');
            }
            $result = true;
        } elseif (!empty($value) && strpos($value, '%') !== false) {
            $select->where->like(addslashes($key), addslashes($value));
            $result = true;
        }
        return $result;
    }
}
