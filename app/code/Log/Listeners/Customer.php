<?php

namespace Redseanet\Log\Listeners;

use Error;
use Exception;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Log\Model\Customer as Log;

class Customer implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Log\Traits\Ip;

    public function log($e)
    {
        $model = $e['model'];
        $request = $this->getContainer()->get('request');
        try {
            $log = new Log();
            $log->setData([
                'customer_id' => $model->getId(),
                'store_id' => Bootstrap::getStore()->getId(),
                'session_id' => $this->getContainer()->get('session')->getId(),
                'remote_addr' => $this->getRealIp(),
                'http_referer' => $request->getHeader('HTTP_REFERER'),
                'http_user_agent' => $request->getHeader('HTTP_USER_AGENT'),
                'http_accept_charset' => $request->getHeader('HTTP_ACCEPT_CHARSET'),
                'http_accept_language' => $request->getHeader('HTTP_ACCEPT_LANGUAGE')
            ]);
            @$log->save();
        } catch (Error $e) {
        } catch (Exception $e) {
        }
    }
}
