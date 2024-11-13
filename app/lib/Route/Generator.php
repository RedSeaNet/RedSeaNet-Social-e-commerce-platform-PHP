<?php

namespace Redseanet\Lib\Route;

use FastRoute\DataGenerator\GroupCountBased;

/**
 * Generate routers' data for cache
 *
 * @todo Adjust other route methods
 */
class Generator extends GroupCountBased
{
    /**
     * @var array
     */
    protected $objectRoutes = [];

    /**
     *
     * @param array $httpMethod
     * @param mixed $routeData
     * @param string $handler
     * @param int $priority
     */
    public function addRoute($httpMethod, $routeData, $handler, $priority = 0)
    {
        if (is_object($routeData)) {
            $this->objectRoutes[] = ['object' => $routeData, 'priority' => $priority];
        } else {
            parent::addRoute($httpMethod, $routeData, $handler);
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        $data = parent::getData();
        usort($this->objectRoutes, function ($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return 0;
            }
            return $a['priority'] > $b['priority'] ? 1 : -1;
        });
        $data[] = $this->objectRoutes;
        return $data;
    }
}
