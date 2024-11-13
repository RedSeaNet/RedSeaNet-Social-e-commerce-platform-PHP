<?php

namespace Redseanet\Catalog\Route;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Http\Request;
use Redseanet\Lib\Route\Route;
use Redseanet\Lib\Route\RouteMatch;

class Router extends Route
{
    use \Redseanet\Lib\Traits\Container;

    public function match(Request $request)
    {
        $path = trim($request->getUri()->getPath(), '/');
        $isJson = false;
        if (substr($path, -5) === '.html') {
            $path = substr($path, 0, -5);
        } elseif (substr($path, -4) === '.htm') {
            $path = substr($path, 0, -4);
        } elseif (substr($path, -5) === '.json') {
            $path = substr($path, 0, -5);
            $isJson = true;
        } else {
            return false;
        }
        if ($isJson && $path === 'catalog/nav') {
            return new RouteMatch([
                'controller' => 'Redseanet\\Catalog\\Controller\\CategoryController',
                'action' => 'nav',
                'is_json' => $isJson
            ], $request);
        }
        if ($result = $this->getContainer()->get('indexer')->select('catalog_url', Bootstrap::getLanguage()->getId(), ['path' => rawurldecode($path)])) {
            if ($result[0]['product_id']) {
                return new RouteMatch([
                    'controller' => $request->getQuery('feed', false) ? 'Redseanet\\Forum\\Controller\\Feed' : 'Redseanet\\Catalog\\Controller\\ProductController',
                    'action' => 'index',
                    'product_id' => $result[0]['product_id'],
                    'category_id' => $result[0]['category_id'],
                    'is_json' => $isJson
                ], $request);
            } else {
                return new RouteMatch([
                    'controller' => 'Redseanet\\Catalog\\Controller\\CategoryController',
                    'action' => 'index',
                    'category_id' => $result[0]['category_id'],
                    'is_json' => $isJson
                ], $request);
            }
        }
        return false;
    }
}
