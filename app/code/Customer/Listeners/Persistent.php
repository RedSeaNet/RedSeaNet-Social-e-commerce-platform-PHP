<?php

namespace Redseanet\Customer\Listeners;

use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Persistent as Model;
use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Session\Segment;

class Persistent implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function validate($e)
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedOut', false)) {
            $segment->offsetUnset('hasLoggedOut');
            if ($key = $this->getContainer()->get('request')->getCookie('persistent')) {
                $persistent = new Model();
                $persistent->load($key, 'key')->remove();
            }
            $this->getContainer()->get('response')->withCookie('persistent', ['value' => '', 'path' => '/', 'expires' => 1]);
        } elseif (!$segment->get('hasLoggedIn') && $key = $this->getContainer()->get('request')->getCookie('persistent')) {
            $persistent = new Model();
            $persistent->load($key, 'key');
            if (strtotime($persistent->offsetGet('updated_at')) < strtotime('+7days')) {
                $customer = new Customer();
                $customer->load($persistent->offsetGet('customer_id'));
                $key = md5(random_bytes(32) . $customer->offsetGet('username'));
                $persistent->setData('key', $key)->save();
                $segment->set('hasLoggedIn', true)
                        ->set('customer', clone $customer);
                $this->getContainer()->get('response')->withCookie('persistent', ['value' => $key, 'path' => '/', 'expires' => time() + 604800]);
            }
        }
    }
}
