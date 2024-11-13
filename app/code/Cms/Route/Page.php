<?php

namespace Redseanet\Cms\Route;

use Redseanet\Cms\Model\Category;
use Redseanet\Cms\Model\Page as Model;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Http\Request;
use Redseanet\Lib\Route\Route;
use Redseanet\Lib\Route\RouteMatch;

class Page extends Route
{
    use \Redseanet\Lib\Traits\Container;

    public function match(Request $request)
    {
        $path = trim($request->getUri()->getPath(), '/');
        $isXml = false;
        $isJson = false;
        if (substr($path, -5) === '.html') {
            $path = substr($path, 0, -5);
        } elseif (substr($path, -5) === '.json') {
            $path = substr($path, 0, -5);
            $isJson = true;
        } elseif (substr($path, -4) === '.htm') {
            $path = substr($path, 0, -4);
        } elseif (substr($path, -4) === '.xml') {
            $path = substr($path, 0, -4);
            $isXml = true;
        } elseif ($path === '') {
            $path = 'home';
        }
        $config = $this->getContainer()->get('config');
        if ($path && Bootstrap::isMobile() && $config['theme/global/layout'] !== $config['theme/global/mobile_layout']) {
            $path .= '-' . $config['theme/global/mobile_layout'];
        }
        if ($path && ($prefix = $this->getContainer()->get('config')['route']['default']['prefix'] ?? '')) {
            $path = $prefix . $path;
        }
        if ($result = $this->getContainer()->get('indexer')->select('cms_url', Bootstrap::getLanguage()->getId(), ['path' => $path])) {
            if ($result[0]['page_id']) {
                return new RouteMatch([
                    'page' => (new Model())->load($result[0]['page_id']),
                    'category' => isset($result[0]['category_id']) ? (new Category())->load($result[0]['category_id']) : null,
                    'namespace' => 'Redseanet\\Cms\\Controller',
                    'controller' => 'PageController',
                    'action' => 'index',
                    'isJson' => $isJson
                ], $request);
            } elseif ($result[0]['category_id']) {
                if ($isXml) {
                    return new RouteMatch([
                        'category' => isset($result[0]['category_id']) ? (new Category())->load($result[0]['category_id']) : null,
                        'namespace' => 'Redseanet\\Cms\\Controller',
                        'controller' => 'FeedController',
                        'action' => 'index'
                    ], $request);
                } elseif ($isJson) {
                    return new RouteMatch([
                        'category' => isset($result[0]['category_id']) ? (new Category())->load($result[0]['category_id']) : null,
                        'namespace' => 'Redseanet\\Cms\\Controller',
                        'controller' => 'PageController',
                        'action' => 'index',
                        'isJson' => $isJson
                    ], $request);
                } else {
                    return new RouteMatch([
                        'category' => isset($result[0]['category_id']) ? (new Category())->load($result[0]['category_id']) : null,
                        'namespace' => 'Redseanet\\Cms\\Controller',
                        'controller' => 'CategoryController',
                        'action' => 'index'
                    ], $request);
                }
            } else {
                return false;
            }
        }
        return false;
    }
}
