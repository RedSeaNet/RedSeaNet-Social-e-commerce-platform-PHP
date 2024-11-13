<?php

namespace Redseanet\Sales\Source;

use Redseanet\I18n\Model\Locate;
use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Sales\Model\Cart;
use Redseanet\Shipping\Model\AbstractMethod;

class ShippingMethod implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function getSourceArray($address = null, $items = [])
    {
        $config = $this->getContainer()->get('config');
        $result = [];
        $countryCode = $address ? (new Locate())->getCode('country', $address->offsetGet('country')) : '';
        foreach ($config['system']['shipping']['children'] as $code => $info) {
            $className = $config['shipping/' . $code . '/model'];
            $country = $config['shipping/' . $code . '/country'];
            $model = new $className();
            $total = 0;
            foreach ($items as $item) {
                if (isset($item['free_shipping']) && !$item['free_shipping'] && !$item['is_virtual']) {
                    $total += $item['base_total'];
                }
            }
            if ($model instanceof AbstractMethod && $model->available(['total' => $total]) &&
                    (!$countryCode || !$country || in_array($countryCode, explode(',', $country)))) {
                $result[$code] = ['label' => $config['shipping/' . $code . '/label'], 'fee' => $model->getShippingRate($items)];
            }
        }
        return $result;
    }
}
