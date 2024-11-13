<?php

namespace Redseanet\Api\Controller;

use Exception;
use Redseanet\Admin\Model\User;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\AbstractController;
use Redseanet\Lib\Session\Csrf;
use Redseanet\Oauth\Model\Consumer;
use Laminas\Crypt\PublicKey\Rsa;
use Laminas\Crypt\PublicKey\RsaOptions;
use Laminas\Crypt\PublicKey\Rsa\PrivateKey;

class RestController extends AbstractController
{
    protected $authOptions = [];

    /**
     * {@inhertdoc}
     */
    public function dispatch($request = null, $routeMatch = null)
    {
        $response = $this->getResponse();
        if (!isset($_SERVER['HTTPS'])) {
            return $response->withStatus(403, 'SSL required');
        }
        if (empty($authorization = $request->getHeader('HTTP_AUTHORIZATION'))) {
            return $response->withStatus(401);
        } else {
            $parts = explode(' ', $authorization);
            $this->authOptions['type'] = ucfirst(strtolower(array_shift($parts)));
            if (!is_callable([$this, $method = 'authorize' . $this->authOptions['type']]) || !$this->$method(implode(' ', $parts))) {
                return $response->withStatus(401);
            }
        }
        return parent::dispatch($request, $routeMatch);
    }

    /**
     * Authorize with oauth token
     *
     * @param string $code
     * @return bool
     */
    public function authorizeOauth($code)
    {
        return $this->authorizeBearer($code);
    }

    /**
     * Authorize with bearer token
     *
     * @param string $code
     * @return bool
     */
    public function authorizeBearer($code)
    {
        $this->authOptions = $this->getContainer()->get('cache')->fetch('$ACCESS_TOKEN$' . $code, 'OAUTH_');
        if ($this->authOptions) {
            $consumer = new Consumer();
            $consumer->load($this->authOptions['consumer_id']);
            if ($consumer->getId() && ($role = $consumer->getRole())) {
                $this->authOptions['type'] = 'Bearer';
                $this->authOptions['role_id'] = $consumer['role_id'];
                $this->authOptions['validation'] = $role['validation'];
                return true;
            }
        }
        return false;
    }

    /**
     * Basic authentication
     *
     * @param string $code
     * @return bool
     */
    public function authorizeBasic($code)
    {
        return false;
    }

    /**
     * Digest authentication
     *
     * @param string $code
     * @return bool
     */
    public function authorizeDigest($code)
    {
        $data = [];
        foreach (preg_split('/\s*\,\s*/', $code) as $item) {
            $pos = strpos($item, '=');
            $data[substr($item, 0, strpos($item, '='))] = trim(substr($item, $pos + 1), ' \'"');
        }
        if (!isset($data['username'])) {
            return false;
        }
        $consumer = new Consumer();
        $consumer->load($data['username'], 'key');
        if ($consumer->getId()) {
            $crypt = new Rsa((new RsaOptions())->setOpensslPadding(OPENSSL_PKCS1_PADDING));
            $response = explode(':', $crypt->decrypt($data['response'], new PrivateKey($consumer->offsetGet('private_key'), $consumer->offsetGet('phrase')), Rsa::MODE_BASE64));
            unset($data['response']);
            ksort($data);
            $valid = implode(':', $data);
            if (count($response) === 3 && $response[2] === md5($response[0] . ':' . $response[1] . ':' . $valid . ':' . $consumer['secret'])) {
                $user = $consumer->getRole()['validation'] === -1 ? (new User()) : (new Customer());
                if ($user->valid($response[0], $response[1])) {
                    $this->authOptions['role'] = $consumer->getRole();
                    $this->authOptions['role_id'] = $consumer['role_id'];
                    $this->authOptions['validation'] = $this->authOptions['role']['validation'];
                    $this->authOptions['open_id'] = false;
                    $this->authOptions['user'] = $user;
                    return true;
                }
            }
        }
        return false;
    }

    public function authorizeCsrf($code)
    {
        if (base64_decode($code) === $this->getContainer()->get('session')->getId()) {
            return true;
        }
        return false;
    }

    public function __call($name, $arguments)
    {
        if ($this->authOptions['type'] === 'Csrf') {
            return $this->getCsrfKey();
        }
        $name = str_replace('_', '', substr($name, 0, -6));
        $class = $this->getContainer()->get('config')['api']['rest'][strtolower($name)] ?? false;
        $method = $this->getRequest()->getMethod() . $name;
        if ($class && is_subclass_of($class, '\\Redseanet\\Api\\Model\\Api\\Rest\\AbstractHandler') && is_callable([$class, $method])) {
            try {
                $response = (new $class())->setAuthOptions($this->authOptions)->$method();
                return $response;
            } catch (Exception $e) {
                return $this->getResponse()->withStatus(400);
            }
        }
        return $this->getResponse()->withStatus(400);
    }

    protected function getCsrfKey()
    {
        return (new Csrf())->getValue();
    }
}
