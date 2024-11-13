<?php

namespace Redseanet\Customer\Controller;

class HistoryController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('customer_history');
    }
}
