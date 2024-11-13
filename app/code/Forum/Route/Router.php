<?php

namespace Redseanet\Forum\Route;

use Redseanet\Forum\Model\Category;
use Redseanet\Forum\Model\Post;
use Redseanet\Lib\Http\Request;
use Redseanet\Lib\Route\Route;
use Redseanet\Lib\Route\RouteMatch;

class Router extends Route
{
    use \Redseanet\Lib\Traits\Container;

    public function match(Request $request)
    {
        $path = trim($request->getUri()->getPath(), '/');
        if (substr($path, -5) === '.html') {
            $path = substr($path, 0, -5);
            $normal = false;
        } elseif (substr($path, -4) === '.htm') {
            $path = substr($path, 0, -4);
            $normal = false;
        } else {
            $normal = true;
        }
        $parts = explode('/', $path);
        if (($key = $this->getContainer()->get('config')['forum/general/uri_key']) && array_shift($parts) !== $key) {
            return false;
        }
        $feed = $request->getQuery('feed', false);
        if (count($parts) === 2) {
            if ($normal) {
                if (class_exists('Redseanet\\Forum\\Controller\\' . ucfirst($parts[0]) . 'Controller')) {
                    $routerClassName = 'Redseanet\\Forum\\Controller\\' . ucfirst($parts[0]) . 'Controller';
                    $routerClass = new $routerClassName();
                    return is_callable([$routerClass, $parts[1] . 'Action']) ?
                            new RouteMatch([
                                'controller' => 'Redseanet\\Forum\\Controller\\' . ucfirst($parts[0]) . 'Controller',
                                'action' => $parts[1],
                            ]) : $this->matchPost($parts, $feed, $request);
                } else {
                    return $this->matchPost($parts, $feed, $request);
                }
            } else {
                return $this->matchPost($parts, $feed, $request);
            }
        } elseif (count($parts) === 1) {
            if ($normal) {
                if (class_exists('Redseanet\\Forum\\Controller\\' . ucfirst($parts[0]) . 'Controller')) {
                    $routerClassName = 'Redseanet\\Forum\\Controller\\' . ucfirst($parts[0]) . 'Controller';
                    $routerClass = new $routerClassName();
                    if (is_callable([$routerClass, 'indexAction'], false)) {
                        return new RouteMatch([
                            'controller' => 'Redseanet\\Forum\\Controller\\' . ucfirst($parts[0]) . 'Controller',
                            'action' => 'index',
                        ]);
                    }
                }
            } else {
                $category = new Category();
                $category->load($parts[0], 'uri_key');
                if ($category->getId()) {
                    return new RouteMatch([
                        'controller' => $feed ? 'Redseanet\\Forum\\Controller\\FeedController' : 'Redseanet\\Forum\\Controller\\CategoryController',
                        'action' => 'index',
                        'category' => $category
                    ], $request);
                } else {
                    return $this->matchPost(['', $parts[0]], $feed, $request);
                }
            }
        } elseif (empty($parts) && !$normal && $request->getQuery('product_id', false)) {
            return new RouteMatch([
                'controller' => 'Redseanet\\Forum\\Controller\\CategoryController',
                'action' => 'product'
            ], $request);
        }
        return false;
    }

    public function matchPost($parts, $feed, $request)
    {
        $category = new Category();
        $category->load($parts[0], 'uri_key');
        if ($category->getId()) {
            $post = new Post();
            $post->load($parts[1], 'uri_key');
            return $post->getId() && $post['category_id'] == $category->getId() ? new RouteMatch([
                'controller' => $feed ? 'Redseanet\\Forum\\Controller\\FeedController' : 'Redseanet\\Forum\\Controller\\PostController',
                'action' => 'index',
                'category' => $category,
                'post' => $post
            ], $request) : false;
        }
        return false;
    }
}
