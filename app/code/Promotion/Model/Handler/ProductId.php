<?php

namespace Redseanet\Promotion\Model\Handler;

class ProductId implements HandlerInterface
{
    public function matchItems($items, $handler)
    {
        $result = [];
        if ($handler['identifier'] === 'product_id') {
            switch ($handler['operator']) {
                case '=':
                    foreach ($items as $id => $item) {
                        if ($item['product_id'] == $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($items as $id => $item) {
                        if ($item['product_id'] != $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
            }
        }
        return $result;
    }
}
