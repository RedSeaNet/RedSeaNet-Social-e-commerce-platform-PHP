<?php

namespace Redseanet\Lib\Controller;

use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Session;

class LanguageController extends ActionController
{
    public function switchAction()
    {
        $code = $this->getOption('code');
        $language = new Language();
        $language->load($code, 'code');
        if ($language->getId()) {
            $segment = new Session\Segment('core');
            $segment->set('language', $code);
            $this->getResponse()->withCookie('language', ['value' => $code, 'path' => '/']);
            $this->getContainer()->get('eventDispatcher')->trigger('language.switch', ['code' => $code]);
        }
        if ($this->getRequest()->isXmlHttpRequest()) {
            return ['redirect' => $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']];
        } else {
            return $this->redirectReferer();
        }
    }
}
