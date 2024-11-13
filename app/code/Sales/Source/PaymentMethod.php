<?php

namespace Redseanet\Sales\Source;

use Redseanet\I18n\Model\Locate;
use Redseanet\Lib\Source\SourceInterface;
use Redseanet\Payment\Model\AbstractMethod;
use Redseanet\Payment\Model\Free;
use Redseanet\Sales\Model\Cart;

class PaymentMethod implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function getSourceArray($address = null, $items = [], $getObject = false)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['base_total'];
        }
        $config = $this->getContainer()->get('config');
        if ($total) {
            $result = [];
            $countryCode = $address ? (new Locate())->getCode('country', $address->offsetGet('country')) : '';
            foreach ($config['system']['payment']['children'] as $code => $info) {
                if ($code === 'payment_free') {
                    continue;
                }
                $className = $config['payment/' . $code . '/model'];
                $country = $config['payment/' . $code . '/country'];
                if (is_array($country)) {
                    $country = $country;
                } elseif (is_string($country)) {
                    $country = explode(',', $country);
                } else {
                    $country = [];
                }
                $approveCountry = false;
                if (!empty($country)) {
                    $approveCountry = true;
                } elseif (!empty($countryCode)) {
                    $approveCountry = true;
                } elseif (in_array($countryCode, $country)) {
                    $approveCountry = true;
                }
                $model = new $className();
                if ($model instanceof AbstractMethod && $model->available(['total' => $total]) === true && $approveCountry) {
                    $result[$code] = $getObject ? $model : $config['payment/' . $code . '/label'];
                }
            }
            return $result;
        } else {
            return ['payment_free' => $getObject ? (new Free()) : $config['payment/payment_free/label']];
        }
    }
}
