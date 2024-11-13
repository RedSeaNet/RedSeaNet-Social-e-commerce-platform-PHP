<?php

namespace Redseanet\Lib\Route;

use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\RouteCollector;

/**
 * Collect routers
 *
 * @todo Adjust other route methods
 */
class Collector extends RouteCollector
{
    protected $dataGenerator;

    public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator)
    {
        $this->dataGenerator = $dataGenerator;
        parent::__construct($routeParser, $dataGenerator);
    }

    /**
     * @param array|string $httpMethod
     * @param string $route
     * @param string $handler
     */
    public function addRoute($httpMethod, $route, $handler, $priority = 0)
    {
        if (class_exists($route)) {
            $route = new $route();
            $this->dataGenerator->addRoute('get', $route, $handler, $priority);
        } else {
            parent::addRoute($httpMethod, $route, $handler);
        }
    }
}
