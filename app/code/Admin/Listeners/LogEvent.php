<?php

namespace Redseanet\Admin\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Session\Segment;

class LogEvent implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function log($event)
    {
        $method = $event['method'];
        if (strpos($method, 'saveAction') !== false) {
            $this->doLog('saved', preg_replace('#Redseanet\\\\Admin\\\\Controller\\\\(.+)Controller#', '$1', get_class($event['controller'])));
        } elseif (strpos($method, 'deleteAction') !== false) {
            $this->doLog('deleted', preg_replace('#Redseanet\\\\Admin\\\\Controller\\\\(.+)Controller#', '$1', get_class($event['controller'])));
        }
    }

    protected function doLog($action, $item)
    {
        $user = (new Segment('admin'))->user;
        $this->getContainer()->get('log')->log($user['username'] . ' has ' . $action . ' ' . $item, 200);
    }
}
