<?php

namespace Redseanet\Promotion\Model\Handler;

class RowTotal implements HandlerInterface
{
    public function matchItems($items, $handler)
    {
        $result = [];
        if ($handler['identifier'] === 'row_total') {
            switch ($handler['operator']) {
                case '=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_total'] === (float) $condition['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_total'] !== (float) $condition['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '>':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_total'] > (float) $condition['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '>=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_total'] >= (float) $condition['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_total'] < (float) $condition['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_total'] <= (float) $condition['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case 'in':
                    foreach ($items as $id => $item) {
                        if (in_array((float) $item['base_total'], explode(',', $condition['value']))) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case 'not in':
                case 'nin':
                    foreach ($items as $id => $item) {
                        if (!in_array((float) $item['base_total'], explode(',', $condition['value']))) {
                            $result[$id] = $item;
                        }
                    }
                    break;
            }
        }
        return $result;
    }
}
