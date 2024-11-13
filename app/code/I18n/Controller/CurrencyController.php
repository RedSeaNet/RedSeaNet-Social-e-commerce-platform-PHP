<?php

namespace Redseanet\I18n\Controller;

use Redseanet\I18n\Model\Currency;
use Redseanet\Lib\Controller\ActionController;

class CurrencyController extends ActionController
{
    public function indexAction()
    {
        $code = $this->getRequest()->getQuery('currency');
        $currency = new Currency();
        $currency->load($code, 'code');
        if ($currency->getId()) {
            $this->getResponse()->withCookie('currency', ['value' => $code, 'path' => '/']);
            $this->getContainer()->get('eventDispatcher')->trigger('currency.switch', ['code' => $code]);
        }
        if ($this->getRequest()->isXmlHttpRequest()) {
            return ['redirect' => $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']];
        } else {
            return $this->redirectReferer();
        }
    }
}
