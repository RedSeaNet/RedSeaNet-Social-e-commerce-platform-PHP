<?php

namespace Redseanet\Admin\Traits;

use DateTime;
use Redseanet\Lib\Db\Sql\Expression\Date;

trait Stat
{
    private $statBy = [
        'd' => 'statByDay',
        'm' => 'statByMonth',
        'y' => 'statByYear',
        'c' => 'statByRange',
    ];

    private function statByDay($collection, $getItem = null, $field = 'created_at')
    {
        $select = $collection->getSelect();
        $group = $select->getRawState('group');
        $group[] = 'date';
        $columns = $select->getRawState('columns');
        $columns['date'] = new Date($field, 'Y-m-d H:' . date('i:s'));
        $select->columns($columns)
                ->reset('group')
                ->group($group)
        ->where->greaterThanOrEqualTo($field, date('Y-m-d H:i:s', strtotime('-24hours')));
        $result = array_fill(1, 24, 0);
        if (count($collection)) {
            $current = new DateTime();
            foreach ($collection as $value) {
                $time = new DateTime(date(DateTime::RFC3339, strtotime($value['date'])));
                $diff = $current->diff($time);
                $item = is_callable($getItem) ? $getItem($value) : ($value['count'] ?? 1);
                $result[$diff->h + 1] += $item;
            }
        }
        return ['filted' => $result];
    }

    private function statByMonth($collection, $getItem = null, $field = 'created_at')
    {
        $select = $collection->getSelect();
        $group = $select->getRawState('group');
        $group[] = 'date';
        $columns = $select->getRawState('columns');
        $columns['date'] = new Date($field, 'Y-m-d ' . date('H:i:s'));
        $select->columns($columns)
                ->reset('group')
                ->group($group)
        ->where->greaterThanOrEqualTo($field, date('Y-m-d H:i:s', strtotime('-30days')));
        $result = array_fill(1, 30, 0);
        if (count($collection)) {
            $current = new DateTime();
            foreach ($collection as $value) {
                $time = new DateTime(date(DateTime::RFC3339, strtotime($value['date'])));
                $diff = $current->diff($time);
                $item = is_callable($getItem) ? $getItem($value) : ($value['count'] ?? 1);
                $result[$diff->d + 1] += $item;
            }
        }
        return ['filted' => $result];
    }

    private function statByYear($collection, $getItem = null, $field = 'created_at')
    {
        $select = $collection->getSelect();
        $group = $select->getRawState('group');
        $group[] = 'date';
        $columns = $select->getRawState('columns');
        $columns['date'] = new Date($field, 'Y-m-' . date('d H:i:s'));
        $select->columns($columns)
                ->reset('group')
                ->group($group)
        ->where->greaterThanOrEqualTo($field, date('Y-m-d H:i:s', strtotime('-1year')));
        $result = array_fill(1, 12, 0);
        if (count($collection)) {
            $current = new DateTime();
            foreach ($collection as $key => $value) {
                $time = new DateTime(date(DateTime::RFC3339, strtotime($value['date'])));
                $diff = $current->diff($time);
                $item = is_callable($getItem) ? $getItem($value) : ($value['count'] ?? 1);
                $result[$diff->m + 1] += $item;
            }
        }
        return ['filted' => $result];
    }

    private function statByRange($collection, $getItem = null, $field = 'created_at')
    {
        $from1 = $this->getRequest()->getQuery('from1');
        $from2 = $this->getRequest()->getQuery('from2');
        $to1 = $this->getRequest()->getQuery('to1');
        $to2 = $this->getRequest()->getQuery('to2');
        $select = $collection->getSelect();
        $select->where->greaterThanOrEqualTo($field, $from1 . ' 0:0:0')
                ->lessThanOrEqualTo($field, $to1 . ' 23:59:59');
        $filted = 0;
        if (count($collection)) {
            foreach ($collection as $key => $value) {
                $item = is_callable($getItem) ? $getItem($value) : ($value['count'] ?? 1);
                $filted += $item;
            }
        }
        $clone = clone $collection;
        $select = $clone->getSelect();
        $select->where->greaterThanOrEqualTo($field, $from2 . ' 0:0:0')
                ->lessThanOrEqualTo($field, $to2 . ' 23:59:59');
        $compared = 0;
        if (count($clone)) {
            foreach ($clone as $key => $value) {
                $item = is_callable($getItem) ? $getItem($value) : ($value['count'] ?? 1);
                $compared += $item;
            }
        }
        return ['filted' => [$from1 . ' - ' . $to1 => $filted, $from2 . ' - ' . $to2 => $compared], 'keys' => [$from1 . ' - ' . $to1, $from2 . ' - ' . $to2]];
    }

    /**
     *
     * @param \Redseanet\Lib\Model\AbstractCollection $collection
     * @param callable $getCount
     * @param callable $getItem
     * @param string $field
     * @return array
     */
    protected function stat($collection, callable $getCount, callable $getItem = null, $field = 'created_at')
    {
        $cache = $this->getContainer()->get('cache');
        $key = md5($collection->getSqlString($this->getContainer()->get('dbAdapter')->getPlatform()));
        $result = $cache->fetch($key, 'STAT_') ?: [];
        if (empty($result)) {
            $clone = clone $collection;
            $clone->load(false);
            $result['amount'] = $getCount($clone);
            $clone = clone $collection;
            $clone->getSelect()->where->greaterThanOrEqualTo($field, date('Y-m-d H:i:s', strtotime('-24hours')));
            $clone->load(false);
            $result['daily'] = $getCount($clone);
            $clone = clone $collection;
            $clone->getSelect()->where->greaterThanOrEqualTo($field, date('Y-m-d H:i:s', strtotime('-30days')));
            $clone->load(false);
            $result['monthly'] = $getCount($clone);
            $clone = clone $collection;
            $clone->getSelect()->where->greaterThanOrEqualTo($field, date('Y-m-d H:i:s', strtotime('-1year')));
            $clone->load(false);
            $result['yearly'] = $getCount($clone);
            $result['time'] = date('Y-m-d H:i:s');
            $cache->save($key, $result, 'STAT_', 3600);
        }
        if ($filter = $this->getRequest()->getQuery('filter')) {
            $key = $this->getRequest()->getUri()->getQuery() . '-' . $key;
            $filted = $cache->fetch($key, 'STAT_');
            if ($filted) {
                $result += $filted;
            } else {
                $filted = $this->{$this->statBy[$filter]}(clone $collection, $getItem, $field);
                if (!isset($filted['keys'])) {
                    $filted['keys'] = array_keys($filted['filted']);
                }
                $result += $filted;
                $cache->save($key, $filted, 'STAT_', 3600);
            }
        }
        return $result;
    }
}
