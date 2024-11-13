<?php

namespace Redseanet\Notifications\Controller;

use Redseanet\Customer\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;

class IndexController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Lib\Traits\Container;

    public function indexAction()
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn')) {
            $root = $this->getLayout('notifications');
            return $root;
        } else {
            return $this->redirect('customer/account/login/');
        }
    }

    public function listAction()
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn')) {
            $root = $this->getLayout('notifications_list');
            return $root;
        } else {
            return $this->redirect('customer/account/login/');
        }
    }
}
