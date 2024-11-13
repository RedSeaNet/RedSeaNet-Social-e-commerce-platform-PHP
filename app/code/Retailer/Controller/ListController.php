<?php

namespace Redseanet\Retailer\Controller;

use Exception;
use Redseanet\Lib\Controller\ActionController;

class ListController extends ActionController
{
    public function indexAction()
    {
        return $this->getLayout('retailer_list');
    }
}
