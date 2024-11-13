<?php

namespace Redseanet\Oauth\Listeners;

use Exception;
use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Session\Segment;
use Redseanet\Oauth\Model\Client as Model;
use Redseanet\Customer\Model\Customer;

class Client implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function bind($e)
    {
        if ($this->getContainer()->get('request')->getPost('use_oauth', false)) {
            $segment = new Segment('oauth');
            $server = $segment->get('server', false);
            $openId = $segment->get('open_id', false);
            $model = $e['model'];
            if (($id = $model->getId()) && $server && $openId) {
                $client = new Model();
                try {
                    $client->setData([
                        'customer_id' => $id,
                        'oauth_server' => $server,
                        'open_id' => $openId
                    ])->save();
                    $segment->offsetUnset('server');
                    $segment->offsetUnset('open_id');
                } catch (Exception $e) {
                }
            }
        }
    }

    public function logout()
    {
        $segment = new Segment('oauth');
        $segment->offsetUnset('server');
        $segment->offsetUnset('open_id');
    }
}
