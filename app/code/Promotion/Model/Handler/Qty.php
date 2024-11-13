<?php

namespace Redseanet\Promotion\Model\Handler;

class Qty implements HandlerInterface
{
    public function matchItems($items, $handler)
    {
        $result = [];
        if ($handler['identifier'] === 'qty') {
            switch ($handler['operator']) {
                case '=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['qty'] === (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['qty'] !== (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '>':
                    foreach ($items as $id => $item) {
                        if ((float) $item['qty'] > (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '>=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['qty'] >= (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<':
                    foreach ($items as $id => $item) {
                        if ((float) $item['qty'] < (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['qty'] <= (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case 'in':
                    foreach ($items as $id => $item) {
                        if (in_array((float) $item['qty'], explode(',', $handler['value']))) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case 'not in':
                case 'nin':
                    foreach ($items as $id => $item) {
                        if (!in_array((float) $item['qty'], explode(',', $handler['value']))) {
                            $result[$id] = $item;
                        }
                    }
                    break;
            }
        }
        return $result;
    }
}
