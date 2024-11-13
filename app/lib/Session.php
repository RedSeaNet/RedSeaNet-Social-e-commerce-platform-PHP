<?php

namespace Redseanet\Lib;

use Redseanet\Lib\Stdlib\Singleton;

/**
 * Session manager
 */
class Session implements Singleton
{
    /**
     * @var Session
     */
    protected static $instance = null;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var array
     */
    protected $cookie_params = [];

    private function __construct($config = [])
    {
        if ($config instanceof Container) {
            $adapter = (array) $config->get('config')['adapter'];
            $sid = $config->has('request') ? $config->get('request')->getQuery('__SID', false) : false;
            $config = $adapter['session'] ?? [];
            if ($sid) {
                $config['id'] = $sid;
            }
        }
        $this->setOptions($config);
        $this->cookie_params = session_get_cookie_params();
    }

    public static function instance($config = [])
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($config);
        }
        return static::$instance;
    }

    public function setOptions(array $config = [])
    {
        foreach ($config as $key => $value) {
            $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (is_callable([$this, $method])) {
                $this->$method($value);
            }
        }
    }

    protected function setSaveHandler($saveHandler)
    {
        if (!is_callable($saveHandler) && class_exists($saveHandler)) {
            $saveHandler = new $saveHandler();
        }
        return session_set_save_handler($saveHandler);
    }

    public function setSavePath($path)
    {
        if (!is_dir(BP . $path)) {
            mkdir(BP . $path, 0777, true);
        }
        return session_save_path(BP . $path);
    }

    public function getSavePath()
    {
        return session_save_path();
    }

    public function sessionExists()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            return true;
        }
        $sid = defined('SID') ? constant('SID') : false;
        if ($sid !== false && $this->getId()) {
            return true;
        }
        if (headers_sent()) {
            return true;
        }
        return false;
    }

    public function regenerateId($deleteOldSession = true)
    {
        session_regenerate_id((bool) $deleteOldSession);
        return $this;
    }

    public function getId()
    {
        return session_id();
    }

    public function setId($id = null)
    {
        session_id($id);
        return $this;
    }

    public function start()
    {
        return session_start();
    }

    public function setName($name)
    {
        if ($this->sessionExists()) {
            throw new \InvalidArgumentException(
                'Cannot set session name after a session has already started'
            );
        }

        if (!preg_match('/^[a-zA-Z0-9]+$/', $name)) {
            throw new \InvalidArgumentException(
                'Name provided contains invalid characters; must be alphanumeric only'
            );
        }

        $this->name = $name;
        session_name($name);
        return $this;
    }

    public function getName()
    {
        if (null === $this->name) {
            $this->name = session_name();
        }
        return $this->name;
    }

    public function clear()
    {
        return session_unset();
    }

    public function commit()
    {
        return session_write_close();
    }

    public function destroy()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->start();
        }

        $name = $this->getName();
        $params = $this->getCookieParams();
        $this->clear();

        $destroyed = session_destroy();
        if ($destroyed) {
            setcookie($name, '', time() - 42000, $params['path'], $params['domain']);
        }

        return $destroyed;
    }

    public function setCookieParams(array $params)
    {
        $this->cookie_params = array_merge($this->cookie_params, $params);
        session_set_cookie_params(
            $this->cookie_params['lifetime'],
            $this->cookie_params['path'],
            $this->cookie_params['domain'],
            $this->cookie_params['secure'],
            $this->cookie_params['httponly']
        );
    }

    public function getCookieParams()
    {
        return $this->cookie_params;
    }

    public function setCacheExpire($expire)
    {
        return session_cache_expire($expire);
    }

    public function getCacheExpire()
    {
        return session_cache_expire();
    }
}
