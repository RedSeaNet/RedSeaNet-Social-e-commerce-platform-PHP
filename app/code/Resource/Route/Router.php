<?php

namespace Redseanet\Resource\Route;

use Redseanet\Lib\Http\Request;
use Redseanet\Lib\Route\Route;
use Redseanet\Lib\Route\RouteMatch;

class Router extends Route
{
    public function match(Request $request)
    {
        $path = $request->getUri()->getPath();
        if (preg_match('#pub/resource/image/resized/(?P<width>\d+)x(?P<height>\d*)/(?P<file>.+\.(?:jpe?g|gif|png|wbmp|xbm))$#', $path, $matches)) {
            return new RouteMatch($matches + ['controller' => '\\Redseanet\\Resource\\Controller\\ResizeController'], $request);
        }
        return false;
    }
}
