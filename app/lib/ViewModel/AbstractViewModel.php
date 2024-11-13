<?php

namespace Redseanet\Lib\ViewModel;

use Redseanet\Lib\Session\Csrf;
use Redseanet\Lib\Stdlib\Singleton;
use Redseanet\Lib\ViewModel\Root;
use Redseanet\Admin\Model\User;
use Redseanet\Lib\Session\Segment;

/**
 * View model for renderer
 */
abstract class AbstractViewModel
{
    use \Redseanet\Lib\Traits\Container,
        \Redseanet\Lib\Traits\Translate,
        \Redseanet\Lib\Traits\Url {
            translate as public;
        }

    /**
     * @var string|false to disable the cache for this view model
     */
    protected $cacheKey = false;

    /**
     * @var Csrf
     */
    protected $csrf = null;

    /**
     * @var array
     */
    protected $query = null;

    /**
     * @var \Redseanet\Lib\Http\Uri
     */
    protected $uri = null;

    /**
     * @var bool
     */
    private static $isAdmin = null;

    /**
     * @var string
     */
    protected $template = null;

    /**
     * @var array Variables
     */
    protected $variables = [];

    /**
     * @var array Children view model
     */
    protected $children = [];

    /**
     * @var \Redseanet\Lib\Http\Request
     */
    protected $request = null;

    /**
     * @var \Redseanet\Lib\Config
     */
    protected $config = null;

    /**
     * @var array
     */
    protected $bannedMember = ['query'];

    public function __toString()
    {
        return $this->render();
    }

    /**
     * Render specified template file with specified renderer
     * Use include by default
     *
     * @return string|mixed
     */
    abstract public function render();

    /**
     * Get template file path
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set template file path
     *
     * @param string $template
     * @param bool $force
     * @return AbstractViewModel
     */
    public function setTemplate($template, $force = true)
    {
        if ($force || empty($this->template)) {
            $this->template = $template;
        }
        return $this;
    }

    /**
     * Get name to cache code
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * Get CSRF key value
     *
     * @return string
     */
    public function getCsrfKey()
    {
        if (is_null($this->csrf)) {
            $this->csrf = (new Csrf())->getValue();
        }
        return $this->csrf;
    }

    /**
     * Get CSRF Element
     *
     * @return string
     */
    public function getCsrfElement()
    {
        return (new Template())->setTemplate('page/csrf');
    }

    /**
     * Get variable or child view model
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
        if (is_callable([$this, $method])) {
            return $this->$method();
        }
        return $this->getVariable($name) ?: $this->getChild($name);
    }

    /**
     * Whether the variable at the specified key exists or not
     *
     * @param string $key
     * @return bool
     */
    public function hasVariable($key)
    {
        return isset($this->variables[$key]);
    }

    /**
     * Returns the variable at the specified key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getVariable($key, $default = '')
    {
        return $this->variables[$key] ?? $default;
    }

    /**
     * Sets the value at the specified key to value
     *
     * @param string $key
     * @param mixed $value
     * @return AbstractViewModel
     */
    public function setVariable($key, $value)
    {
        $this->variables[$key] = $value;
        return $this;
    }

    /**
     * Get variables and children view models
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables + $this->children;
    }

    /**
     * Sets variables
     *
     * @param array $variables
     * @return AbstractViewModel
     */
    public function setVariables(array $variables)
    {
        foreach ($variables as $key => $value) {
            $this->setVariable($key, $value);
        }
        return $this;
    }

    /**
     * Get child view model
     *
     * @param string $name
     * @param bool $recursive
     * @return AbstractViewModel
     */
    public function getChild($name = null, $recursive = false)
    {
        if (is_null($name)) {
            return $this->children;
        } elseif (isset($this->children[$name])) {
            return $this->children[$name];
        } elseif ($recursive) {
            foreach ($this->children as $value) {
                $child = $value->getChild($name, $recursive);
                if (!is_null($child)) {
                    return $child;
                }
            }
        }
        return null;
    }

    /**
     * Add child view model
     *
     * @param string $name
     * @param AbstractViewModel $child
     * @return AbstractViewModel
     */
    public function addChild($name, AbstractViewModel $child)
    {
        $this->children[$name] = $child;
        return $this;
    }

    /**
     * Serialize this view model
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(
            array_filter(get_object_vars($this), function ($value) {
                return !is_object($value);
            })
        );
    }

    /**
     * Unserialize this view model
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach ($data as $key => $value) {
            if (!in_array($key, $this->bannedMember)) {
                $this->$key = $value;
            }
        }
        if ($this instanceof Singleton) {
            static::$instance = $this;
        }
    }

    /**
     * Get request
     *
     * @return \Redseanet\Lib\Http\Request
     */
    protected function getRequest()
    {
        if (is_null($this->request)) {
            $this->request = $this->getContainer()->get('request');
        }
        return $this->request;
    }

    /**
     * Get request query
     *
     * @return array
     */
    public function getQuery($key = null, $default = '')
    {
        if (is_null($this->query)) {
            $this->query = $this->getRequest()->getQuery();
        }
        return is_null($key) ? $this->query : ($this->query[$key] ?? $default);
    }

    /**
     * Get request uri
     *
     * @return \Redseanet\Lib\Http\Uri
     */
    public function getUri()
    {
        if (is_null($this->uri)) {
            $this->uri = $this->getRequest()->getUri();
        }
        return $this->uri;
    }

    /**
     * Is admin page
     *
     * @return bool
     */
    public function isAdminPage()
    {
        if (is_null(self::$isAdmin)) {
            self::$isAdmin = in_array('admin', Root::instance()->getBodyClass(true));
        }
        return self::$isAdmin;
    }

    /**
     * Whether current role has specific permission
     *
     * @param string $key
     * @return bool
     */
    public function hasPermission($key)
    {
        if ($this->isAdminPage()) {
            $userArray = (new Segment('admin'))->get('user');
            $user = new User();
            $user->load($userArray['id']);
            return $user->getRole()->hasPermission($key);
        }
        return false;
    }

    /**
     * Get system config
     *
     * @return \Redseanet\Lib\Config
     */
    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = $this->getContainer()->get('config');
        }
        return $this->config;
    }
}
