<?php

namespace Redseanet\Lib;

use Exception;
use Redseanet\Lib\Stdlib\ArrayObject;
use Redseanet\Lib\Stdlib\Singleton;

/**
 * Page layout manager
 */
class Layout extends ArrayObject implements Singleton
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\ArrayMerge;

    protected static $layout = null;

    protected function __construct($config = [])
    {
        if ($config instanceof Container) {
            $this->setContainer($config);
            $config = [];
        }
        if (empty($config)) {
            $config = $this->getContainer()->get('config')['layout'];
        }
        $this->storage = $config[Bootstrap::isMobile() ? 'mobile' : 'pc'];
    }

    public static function instance($config = [])
    {
        if (is_null(static::$layout)) {
            static::$layout = new static($config);
        }
        return static::$layout;
    }

    /**
     * Get page layout
     *
     * @param string $handler
     * @param bool $render
     * @return array|ViewModel\Root
     */
    public function getLayout($handler = '', $render = false)
    {
        if (empty($this->storage[$handler])) {
            return '';
        }
        $layout = $this->storage[$handler];
        if (isset($this->storage[$handler]['update'])) {
            unset($layout['update']);
            $layout = $this->arrayMerge($this->getLayout($this->storage[$handler]['update']), $layout);
        }
        if ($render) {
            if (empty($layout)) {
                return '';
            }
            $root = $this->renderLayout($layout['root'], 'root');
            $root->setHandler($handler);
            return $root;
        }
        return $layout;
    }

    /**
     *
     * @param array $layout
     * @param string $name
     * @param ViewModel\AbstractViewModel $parent
     * @return ViewModel\AbstractViewModel
     */
    public function renderLayout(array $layout, $name, $parent = null)
    {
        if (!isset($layout['type']) || !class_exists($layout['type'])) {
            $this->getContainer()->get('log')->logException(new Exception('Class not found: ' . ($layout['type'] ?? '')));
            return null;
        }
        if (is_subclass_of($layout['type'], '\\Redseanet\\Lib\\Stdlib\\Singleton')) {
            $viewModel = $layout['type']::instance();
        } else {
            $viewModel = new $layout['type']();
        }
        if (isset($layout['template'])) {
            $viewModel->setTemplate($layout['template']);
        }
        if (isset($layout['action'])) {
            if (isset($layout['action']['method'])) {
                $layout['action'] = [$layout['action']];
            }
            foreach ($layout['action'] as $action) {
                if (is_callable([$viewModel, $action['method']])) {
                    call_user_func_array([$viewModel, $action['method']], isset($action['params']) ? (array) $action['params'] : []);
                }
            }
        }
        if (isset($layout['children'])) {
            foreach ($layout['children'] as $childName => $children) {
                if (isset($layout['unset']) && in_array($childName, $layout['unset'])) {
                    continue;
                }
                $this->renderLayout($children, $childName, $viewModel);
            }
        }
        if (!is_null($parent)) {
            $parent->addChild($name, $viewModel);
        }
        return $viewModel;
    }
}
