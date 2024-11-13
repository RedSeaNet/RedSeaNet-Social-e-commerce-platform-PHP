<?php

namespace Redseanet\Api\Model\Api;

use SoapFault;
use Redseanet\Api\Model\Soap\User;
use Redseanet\Api\Model\Soap\Session;

final class General extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * @param string $username
     * @param string $password
     * @return string
     */
    public function login($username, $password)
    {
        $user = new User();
        $user->load($username, 'username');
        if ($user->getId() && $user->valid($username, $this->decryptData($password, $user))) {
            $session = new Session();
            $session->setData('user_id', $user->getId())
                    ->save();
            return $session->getId();
        }
        return new SoapFault('Client', 'Invalid username or password.');
    }

    /**
     * @param string $sessionId
     * @return bool
     */
    public function endSession($sessionId)
    {
        $this->validateSessionId($sessionId, __FUNCTION__);
        $this->session->remove();
        return true;
    }

    /**
     * @param string $key
     * @param string $prefix
     */
    public function flushCache($key, $prefix)
    {
        $this->getContainer()->get('cache')->delete($key, $prefix, false);
    }
}
