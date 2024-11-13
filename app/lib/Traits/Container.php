<?php

namespace Redseanet\Lib\Traits;

use Psr\Container\ContainerInterface;
use Redseanet\Lib\Bootstrap;

/**
 * Get/Set DI Container
 */
trait Container
{
    /**
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (is_null($this->container)) {
            $this->container = Bootstrap::getContainer();
        }
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     * @return self
     */
    protected function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }
}
