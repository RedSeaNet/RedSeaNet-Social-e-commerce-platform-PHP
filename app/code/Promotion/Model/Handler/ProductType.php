<?php

namespace Redseanet\Promotion\Model\Handler;

class ProductType implements HandlerInterface
{
    public function matchItems($items, $handler)
    {
        $result = [];
        if ($handler['identifier'] === 'product_type') {
            switch ($handler['operator']) {
                case '=':
                    foreach ($items as $id => $item) {
                        if ($item['product']['product_type_id'] == $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
                case '<>':
                case '!=':
                    foreach ($items as $id => $item) {
                        if ($item['product']['product_type_id'] != $handler['value']) {
                            $result[$id] = $item;
                        }
                    }
                    break;
            }
        }
        return $result;
    }
}
