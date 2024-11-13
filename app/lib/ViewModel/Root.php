<?php

namespace Redseanet\Lib\ViewModel;

use Redseanet\Lib\Stdlib\Singleton;

/**
 * Root view model
 */
final class Root extends Template implements Singleton
{
    protected static $instance = null;
    protected $bodyClass = [];
    protected $handler = '';

    private function __construct()
    {
        $this->setTemplate('page/root');
    }

    /**
     * @return Root
     */
    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Get class attribute of body element
     *
     * @param bool $asArray
     * @return array|string
     */
    public function getBodyClass($asArray = false)
    {
        return $asArray ? $this->bodyClass : implode(' ', $this->bodyClass);
    }

    /**
     * Set class attribute of body element
     *
     * @param array $bodyClass
     * @return Root
     */
    public function setBodyClass(array $bodyClass)
    {
        $this->bodyClass = $bodyClass;
        return $this;
    }

    /**
     * Add a class to body element
     *
     * @param array $bodyClass
     * @return Root
     */
    public function addBodyClass($bodyClass)
    {
        $this->bodyClass[] = $bodyClass;
        return $this;
    }

    /**
     * Get lang attribute for html element
     *
     * @return string
     */
    public function getLang()
    {
        return $this->getContainer()->get('language')['code'];
    }

    /**
     * Get layout handler
     *
     * @return string
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Set layout handler
     *
     * @param string $handler
     * @return Root
     */
    public function setHandler($handler)
    {
        if ($this->handler === '') {
            $this->addBodyClass(trim(preg_replace('/[^a-z]+/', '-', strtolower($handler)), '-'));
        }
        $this->handler = $handler;
        return $this;
    }
}
