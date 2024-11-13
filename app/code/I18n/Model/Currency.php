<?php

namespace Redseanet\I18n\Model;

use NumberFormatter;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Bootstrap;

class Currency extends AbstractModel
{
    protected $numberFormatter = null;

    protected function construct()
    {
        $this->init('i18n_currency', 'id', ['id', 'code', 'symbol', 'rate', 'format']);
    }

    public function convert($price, $format = false)
    {
        if (isset($this->storage['rate'])) {
            if (function_exists('bcmul')) {
                $price = bcmul(floatval($price), $this->storage['rate'], 4);
            } else {
                $price *= $this->storage['rate'];
            }
        }
        return $format ? $this->format($price) : $price;
    }

    public function rconvert($price)
    {
        if (isset($this->storage['rate'])) {
            if (function_exists('bcdiv')) {
                $price = bcdiv($price, $this->storage['rate'], 4);
            } else {
                $price /= $this->storage['rate'];
            }
        }
        return $price;
    }

    public function format($price)
    {
        if (isset($this->storage['format'])) {
            return sprintf($this->storage['format'], $this->storage['symbol'], $price);
        } elseif (extension_loaded('intl')) {
            if (is_null($this->numberFormatter)) {
                $this->numberFormatter = new NumberFormatter(Bootstrap::getLanguage()['code'], NumberFormatter::CURRENCY);
            }
            return $this->numberFormatter->formatCurrency($price, $this->storage['symbol']);
        }
        return $price;
    }
}
