<?php

namespace Redseanet\Customer\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Laminas\Crypt\BlockCipher;
use Laminas\Crypt\Symmetric\Openssl;

abstract class AuthActionController extends ActionController
{
    protected $allowedAction = [];

    public function doDispatch($method = 'notFoundAction')
    {
        $action = strtolower(substr($method, 0, -6));
        $session = new Segment('customer');
        if (!in_array($action, $this->allowedAction) && !$session->get('hasLoggedIn', false)) {
            return $this->getRequest()->isXmlHttpRequest() ?
                    $this->getResponse()->withStatus(403) :
                    $this->redirect('customer/account/login/');
        }
        return parent::doDispatch($method);
    }

    protected function useSso(&$result)
    {
        $config = $this->getContainer()->get('config');
        if ($config['customer/login/sso'] && !empty($result['success_url']) && $config['customer/login/allowed_sso_url'] && in_array(parse_url($result['success_url'], PHP_URL_HOST), explode(';', $config['customer/login/allowed_sso_url']))) {
            $result['message'] = [];
            $cipher = new BlockCipher(new Openssl());
            $cipher->setKey($config['customer/login/sso_key']);
            $result['success_url'] .= '?token=' . str_replace(['+', '/', '='], ['-', '_', ''], urlencode($cipher->encrypt(urlencode($result['data']))));
            return true;
        }
        return false;
    }
}
