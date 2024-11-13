<?php

namespace Redseanet\Lib\Controller;

use Exception;
use Redseanet\Admin\Model\User;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Route\RouteMatch;
use Redseanet\Lib\Session\Segment;

/**
 * Controller with authorization for backend request
 */
class AuthActionController extends ActionController
{
    use \Redseanet\Lib\Traits\DB;

    /**
     * {@inheritdoc}
     */
    public function dispatch($request = null, $routeMatch = null)
    {
        $this->request = $request;
        $cors = $this->getContainer()->get('config')['adapter']['cors'] ?? [];
        if ($cors && in_array($this->getRequest()->getUri()->getHost(), (array) $cors)) {
            $this->getResponse()->withHeader('Access-Control-Allow-Origin', $this->getRequest()->getUri()->getHost());
        }
        if ($this->getRequest()->isOptions() && $this->getRequest()->getHeader('Access-Control-Request-Method')['Access-Control-Request-Method']) {
            $this->getResponse()->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
            return $this->getResponse();
        } elseif (!$routeMatch instanceof RouteMatch) {
            $method = 'notFoundAction';
        } else {
            $method = $routeMatch->getMethod();
            $this->options = $routeMatch->getOptions();
            $segment = new Segment('admin');
            $userArray = $segment->get('user');
            $user = new User();
            $permission = str_replace('Redseanet\\', '', preg_replace('/Controller(?:\\\\)?/', '', get_class($this))) . '::' . str_replace('Action', '', $method);
            if (!$segment->get('hasLoggedIn')) {
                if (!isset($_SERVER['HTTPS']) || $segment->get('lock_auth', false)) {
                    return $this->notFoundAction();
                }
                $flag = true;
                if ($flag && isset($this->getRequest()->getHeader('HTTP_AUTHORIZATION')['HTTP_AUTHORIZATION']) && $this->getRequest()->getHeader('HTTP_AUTHORIZATION')['HTTP_AUTHORIZATION']) {
                    $flag = false;
                    $cache = $this->getContainer()->get('cache');
                    $username = $this->getRequest()->getHeader('PHP_AUTH_USER')['PHP_AUTH_USER'];
                    $user = new User();
                    if ($cache->fetch('lock_auth_' . $username) || !$user->login($username, $this->getRequest()->getHeader('PHP_AUTH_PW')['PHP_AUTH_PW'])) {
                        $cache->save('lock_auth_' . $username, 1, '', 3600);
                        $segment->set('lock_auth', true);
                        return $this->notFoundAction();
                    }
                }
                if ($flag) {
                    return $this->getResponse()->withStatus('401')
                                    ->withHeader('WWW-Authenticate', 'Basic realm="' . Bootstrap::getMerchant()['name'] . '"');
                }
            } elseif (!($user->load($userArray['id']))->getRole()->hasPermission($permission)) {
                return $this->notFoundAction();
            }
            if (!is_callable([$this, $method])) {
                $method = 'notFoundAction';
            }
        }
        return $this->doDispatch($method);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDispatch($method = 'notFoundAction')
    {
        if ($method !== 'notFoundAction') {
            $param = ['controller' => $this, 'method' => $method];
            $dispatcher = $this->getContainer()->get('eventDispatcher');
            $dispatcher->trigger(get_class($this) . '.dispatch.before', $param);
            $dispatcher->trigger('auth.dispatch.before', $param);
            $dispatcher->trigger('dispatch.before', $param);
        }
        $result = $this->$method();
        if ($method !== 'notFoundAction') {
            $param = ['controller' => $this, 'method' => $method, 'result' => &$result];
            $dispatcher = $this->getContainer()->get('eventDispatcher');
            $dispatcher->trigger(get_class($this) . '.dispatch.after', $param);
            $dispatcher->trigger('auth.dispatch.after', $param);
            $dispatcher->trigger('dispatch.after', $param);
        }
        return $result;
    }

    protected function doDelete($modelName, $redirect = null, $beforeDelete = null)
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if (is_null($beforeDelete)) {
                $beforeDelete = function ($model, $id) {
                    $model->setId($id);
                    return true;
                };
            }
            if ($result['error'] === 0) {
                try {
                    $model = is_object($modelName) && $modelName instanceof AbstractModel ? $modelName : new $modelName();
                    $count = 0;
                    foreach ((array) $data['id'] as $id) {
                        if ($beforeDelete($model, $id)) {
                            $model->remove();
                            $count++;
                        }
                    }
                    $result['message'][] = ['message' => $this->translate('%d item(s) have been deleted successfully.', [$count]), 'level' => 'success'];
                    $result['removeLine'] = (array) $data['id'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], is_null($redirect) ? $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] : $redirect);
    }

    protected function doSave($modelName, $redirect = null, $required = [], callable $beforeSave = null, $transaction = false, callable $afterSave = null)
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, $required);
            if ($result['error'] === 0) {
                if (is_subclass_of($modelName, '\\Redseanet\\Lib\\Model\\Eav\\Entity')) {
                    $model = new $modelName($data['language_id'] ?? Bootstrap::getLanguage()->getId(), $data);
                } else {
                    $model = new $modelName($data);
                }
                if (!isset($data[$model->getPrimaryKey()]) || (int) $data[$model->getPrimaryKey()] === 0) {
                    $model->setId(null);
                }
                try {
                    if ($transaction) {
                        $this->beginTransaction();
                    }
                    if (is_callable($beforeSave)) {
                        $beforeSave($model, $data);
                    }
                    $model->save();
                    if (is_callable($afterSave)) {
                        $afterSave($model);
                    }
                    $result['data'] = $model->getArrayCopy();
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                    if ($transaction) {
                        $this->commit();
                    }
                } catch (Exception $e) {
                    if ($transaction) {
                        $this->rollback();
                    }
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], is_null($redirect) ? $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] : $redirect);
    }
}
