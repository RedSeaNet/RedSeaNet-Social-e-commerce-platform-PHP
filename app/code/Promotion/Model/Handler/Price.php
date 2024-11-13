<?php

namespace Redseanet\Promotion\Model\Handler;

class Price implements HandlerInterface
{
    public function matchItems($items, $handler)
    {
        $result = [];
        if ($handler['identifier'] === 'price') {
            switch ($handler['operator']) {
                case '=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_price'] === (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_price'] !== (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '>':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_price'] > (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '>=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_price'] >= (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_price'] < (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<=':
                    foreach ($items as $id => $item) {
                        if ((float) $item['base_price'] <= (float) $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case 'in':
                    foreach ($items as $id => $item) {
                        if (in_array((float) $item['base_price'], explode(',', $handler['value']))) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case 'not in':
                case 'nin':
                    foreach ($items as $id => $item) {
                        if (!in_array((float) $item['base_price'], explode(',', $handler['value']))) {
                            $result[$id] = $item;
                        }
                    }
                    break;
            }
        }
        return $result;
    }
}
