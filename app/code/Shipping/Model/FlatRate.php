<?php

namespace Redseanet\Shipping\Model;

class FlatRate extends AbstractMethod
{
    public const METHOD_CODE = 'flat_rate';

    public function getShippingRate($items)
    {
        $config = $this->getContainer()->get('config');
        $calc = $config['shipping/' . self::METHOD_CODE . '/calc'];
        $rate = $config['shipping/' . self::METHOD_CODE . '/rate'];
        if ($config['shipping/' . self::METHOD_CODE . '/unit']) {
            if (!$calc) {
                return $rate;
            }
            $total = 0;
            foreach ($items as $item) {
                if (!$item['free_shipping'] && !$item['is_virtual']) {
                    $total += $item->offsetGet('base_total');
                }
            }
            return $total * $rate / 100;
        } else {
            $result = 0;
            foreach ($items as $item) {
                if (!$item['free_shipping'] && !$item['is_virtual']) {
                    if ($calc) {
                        $result += $item->offsetGet('base_total') * $rate / 100;
                    } else {
                        $result += $rate * $item->offsetGet('qty') / 100;
                    }
                }
            }
            return $result;
        }
    }
}
