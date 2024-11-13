<?php

namespace Redseanet\Admin\Route;

use Redseanet\Lib\Http\Request;
use Redseanet\Lib\Route\Route;
use Redseanet\Lib\Route\RouteMatch;

class Router extends Route
{
    use \Redseanet\Lib\Traits\Container;

    public function match(Request $request)
    {
        $path = trim($request->getUri()->getPath(), '/');
        $parts = explode('/', $path);
        if ($parts[0] === $this->getContainer()->get('config')['global/url/admin_path']) {
            $options = ['namespace' => 'Redseanet\\Admin\\Controller'];
            if (isset($parts[1])) {
                $options['controller'] = str_replace(' ', '\\', ucwords(str_replace('_', ' ', $parts[1]))) . 'Controller';
            } else {
                $options['controller'] = 'IndexController';
            }
            if (isset($parts[2])) {
                $options['action'] = $parts[2];
            } else {
                $options['action'] = 'index';
            }
            if (class_exists($options['namespace'] . '\\' . $options['controller'])) {
                return new RouteMatch($options, $request);
            }
        }
        return false;
    }
}
