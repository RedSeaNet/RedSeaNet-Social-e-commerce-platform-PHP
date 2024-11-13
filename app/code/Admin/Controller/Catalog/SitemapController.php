<?php

namespace Redseanet\Admin\Controller\Catalog;

use Redseanet\Lib\Controller\AuthActionController;

class SitemapController extends AuthActionController
{
    use \Redseanet\Catalog\Traits\Sitemap;

    public function indexAction()
    {
        return $this->response($this->generate(), (!empty($this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']) ? $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] : ''));
    }
}
