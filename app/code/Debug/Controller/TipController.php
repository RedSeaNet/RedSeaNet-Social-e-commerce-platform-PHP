<?php

namespace Redseanet\Debug\Controller;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;

class TipController extends ActionController
{
    public function switchAction()
    {
        if (!Bootstrap::isDeveloperMode()) {
            return $this->notFoundAction();
        }
        $segment = new Segment('debug');
        $segment->set('tip', !$segment->get('tip', false));
        return $this->response(['error' => 0, 'message' => [], 'reload' => 1], '', 'core');
    }
}
