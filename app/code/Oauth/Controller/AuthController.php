<?php

namespace Redseanet\Oauth\Controller;

use Redseanet\Admin\Model\User;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Oauth\Model\Collection\Token as TokenCollection;
use Redseanet\Oauth\Model\Consumer;
use Redseanet\Oauth\Model\Token;
use Laminas\Math\Rand;

class AuthController extends ActionController
{
    protected $cache = null;

    public function dispatch($request = null, $routeMatch = null)
    {
        if (!isset($_SERVER['HTTPS'])) {
            return $this->getResponse()->withStatus(403, 'SSL required');
        }
        return parent::dispatch($request, $routeMatch);
    }

    public function indexAction()
    {
        $query = $this->getRequest()->getQuery();
        if (!isset($query['response_type']) ||
                $query['response_type'] !== 'code' ||
                !isset($query['client_id']) ||
                !isset($query['redirect_url'])) {
            return $this->getResponse()->withStatus('400');
        }
        $consumer = new Consumer();
        $consumer->load($query['client_id'], 'key');
        if (!$consumer->getId() && strpos(base64_decode($query['redirect_url']), $consumer['callback_url']) !== 0) {
            return $this->getResponse()->withStatus('400');
        } elseif ($consumer->getRole()['validation'] === '0') {
            return $this->grant($consumer, $query['redirect_url']);
        } else {
            $root = $this->getLayout('oauth_login');
            $root->getChild('form', true)->setConsumer($consumer);
            return $root;
        }
    }

    protected function grant($consumer, $callback = '', $user = null)
    {
        if (is_null($this->cache)) {
            $this->cache = $this->getContainer()->get('cache');
        }
        do {
            $code = Rand::getString(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        } while ($this->cache->fetch('$AUTHORIZATION_CODE$' . $code, 'OAUTH_'));
        if (empty($callback)) {
            $callback = $consumer['callback_url'];
        } else {
            $callback = base64_decode($callback);
            if (strpos($callback, $consumer['callback_url']) !== 0) {
                return $this->getResponse()->withStatus(400);
            }
        }
        $data = [
            'consumer_id' => $consumer->getId(),
            'redirect_url' => $callback
        ];
        if ($user) {
            $data['user_id'] = $user->getId();
        }
        $this->cache->save('$AUTHORIZATION_CODE$' . $code, $data, 'OAUTH_', 600);
        return $this->redirect($callback .
                        (strpos($callback, '?') === false ? '?' : '&') .
                        'authorization_code=' . $code .
                        (isset($data['state']) ?
                        '&state=' . $data['state'] : ''));
    }

    public function loginAction()
    {
        $data = $this->getRequest()->getPost();
        $result = $this->validateForm($data, ['username', 'password', 'response_type', 'client_id']);
        if ($result['error'] === 0) {
            $consumer = new Consumer();
            $consumer->load($data['client_id'], 'key');
            if ($consumer->getId()) {
                $user = $consumer->getRole()['validation'] === -1 ? (new User()) : (new Customer());
                if ($user->valid($data['username'], $data['password'])) {
                    return $this->grant($consumer, $data['redirect_url'], $user);
                }
            } else {
                return $this->getResponse()->withStatus('400');
            }
        }
        if (isset($result)) {
            $this->addMessage($result['message'], 'danger', 'oauth');
        }
        unset($data['csrf'], $data['username'], $data['password']);

        return $this->redirectReferer('oauth/auth/?' . http_build_query($data));
    }

    public function tokenAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (isset($data['code']) && isset($data['client_id']) &&
                    isset($data['client_secret']) && isset($data['redirect_url'])) {
                $cache = $this->getContainer()->get('cache');
                $info = $cache->fetch('$AUTHORIZATION_CODE$' . $data['code'], 'OAUTH_');
                if ($info) {
                    $consumer = new Consumer();
                    $consumer->load($info['consumer_id']);
                    if ($consumer['key'] != $data['client_id'] || $consumer['secret'] != $data['client_secret']) {
                        return $this->getResponse()->withStatus(400);
                    }
                    if ($consumer->getId() && base64_decode($data['redirect_url']) === $info['redirect_url']) {
                        if ($consumer->getRole()['validation'] == 0) {
                            $user = true;
                        } else {
                            $user = $consumer->getRole()['validation'] === -1 ? (new User()) : (new Customer());
                            $user->load($info['user_id']);
                        }
                        if ($user === true || $user->getId()) {
                            $constraint = [
                                'consumer_id' => $consumer->getId()
                            ];
                            if ($user !== true) {
                                $constraint[($consumer->getRole()['validation'] === -1 ? 'admin_id' : 'customer_id')] = $user->getId();
                            }
                            $collection = new TokenCollection();
                            $collection->columns(['open_id'])->where($constraint);
                            if (!count($collection)) {
                                do {
                                    $constraint['open_id'] = Rand::getString(32, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                                    $collection->reset('where')->where($constraint);
                                } while (count($collection));
                                $token = new Token();
                                $token->setData($constraint)->save();
                                $openId = $constraint['open_id'];
                            } else {
                                $openId = $collection[0]['open_id'];
                            }
                            do {
                                $code = Rand::getString(32, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
                            } while ($cache->fetch('$ACCESS_TOKEN$' . $code, 'OAUTH_'));
                            $data = ['consumer_id' => $consumer->getId(), 'open_id' => $openId];
                            if ($user !== true) {
                                $data['user_id'] = $user->getId();
                            }
                            $cache->save('$ACCESS_TOKEN$' . $code, $data, 'OAUTH_', 3600);
                            return ['access_token' => $code, 'open_id' => $openId, 'expired_at' => date('l, d-M-Y H:i:s T', time() + 3600)];
                        }
                    }
                }
            }
            return $this->getResponse()->withStatus(400);
        }
        return $this->getResponse()->withStatus(405);
    }

    public function touchAction()
    {
        $token = $this->getRequest()->getQuery('access_token');
        $cache = $this->getContainer()->get('cache');
        $data = $cache->fetch('$ACCESS_TOKEN$' . $token, 'OAUTH_');
        if ($data) {
            $cache->save('$ACCESS_TOKEN$' . $token, $data, 'OAUTH_', 3600);
            return ['access_token' => $token, 'expired_at' => date('l, d-M-Y H:i:s T', time() + 3600)];
        }
        return $this->getResponse()->withStatus(400);
    }
}
