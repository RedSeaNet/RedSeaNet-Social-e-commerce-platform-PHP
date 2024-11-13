<?php

namespace Redseanet\I18n\ViewModel;

use Redseanet\Lib\ViewModel\Template;

class Currency extends Template
{
    protected $wrapper = 'div';

    public function __construct()
    {
        $this->setTemplate('i18n/currency');
    }

    public function getCurrency()
    {
        $currency = $this->getConfig()['i18n/currency/enabled'];
        return is_array($currency) ? $currency : explode(',', $currency);
    }

    public function getCurrentCurrency()
    {
        return $this->getRequest()->getCookie('currency', $this->getConfig()['i18n/currency/base']);
    }

    public function getUrl($currency)
    {
        return $this->getBaseUrl('i18n/currency/?currency=' . $currency);
    }

    public function getWrapper()
    {
        return $this->wrapper;
    }

    public function setWrapper($wrapper)
    {
        $this->wrapper = $wrapper;
        return $this;
    }
}
