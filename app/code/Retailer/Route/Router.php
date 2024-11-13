<?php

namespace Redseanet\Retailer\Route;

use Redseanet\Lib\Http\Request;
use Redseanet\Lib\Route\Route;
use Redseanet\Lib\Route\RouteMatch;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Retailer\Model\Collection\Category;

class Router extends Route
{
    use \Redseanet\Lib\Traits\Container;

    public function match(Request $request)
    {
        $path = trim($request->getUri()->getPath(), '/');
        if (substr($path, -5) === '.html') {
            $path = substr($path, 0, -5);
        } elseif (substr($path, -4) === '.htm') {
            $path = substr($path, 0, -4);
        } else {
            return false;
        }
        $parts = explode('/', $path);
        $count = count($parts);
        if ($count <= 1 || array_shift($parts) !== 'store') {
            return false;
        }
        $retailer = new Retailer();
        $retailer->load(rawurldecode(array_shift($parts)), 'uri_key');
        if ($retailer->getId()) {
            if ($count === 2) {
                return new RouteMatch([
                    'controller' => 'Redseanet\\Retailer\\Controller\\ViewController',
                    'action' => 'index',
                    'store_id' => $retailer->getStore()->getId(),
                    'retailer' => $retailer
                ], $request);
            } else {
                $categories = new Category();
                $categories->where(['store_id' => $retailer->getStore()->getId()])
                ->where->in('uri_key', $parts);
                $last = null;
                foreach ($parts as $part) {
                    foreach ($categories as $category) {
                        if ($category->offsetGet('uri_key') === $part) {
                            if (is_null($last) || $category->offsetGet('parent_id') === $last->getId()) {
                                $last = $category;
                                break;
                            } else {
                                return false;
                            }
                        }
                    }
                }
                return new RouteMatch([
                    'controller' => 'Redseanet\\Retailer\\Controller\\ViewController',
                    'action' => 'category',
                    'category' => $last
                ], $request);
            }
        }
        return false;
    }
}
