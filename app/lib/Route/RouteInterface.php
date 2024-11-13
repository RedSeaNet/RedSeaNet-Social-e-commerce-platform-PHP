<?php

namespace Redseanet\Lib\Route;

use Redseanet\Lib\Http\Request;

interface RouteInterface
{
    /**
     * Match request
     *
     * @param Request $request
     * @return RouteMatch|false when dismatch the request
     */
    public function match(Request $request);
}
