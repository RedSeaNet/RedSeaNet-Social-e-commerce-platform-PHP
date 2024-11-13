<?php

namespace Redseanet\Lib;

use Redseanet\Lib\Stdlib\Singleton;
use Redseanet\Lib\Stdlib\ArrayObject;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Main configuration of system
 */
final class Config extends ArrayObject implements Singleton
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\ArrayMerge;

    protected static $instance = null;
    protected $keys = [];
    protected $cache = [];

    /**
     * A pattern (a regexp, a glob, or a string)
     *
     * @var string
     */
    protected $bannedYml = 'rbac.yml';

    /**
     * @param array|Container $config
     */
    private function __construct($config = [])
    {
        if ($config instanceof Container) {
            $this->setContainer($config);
            $config = [];
        }
        $this->storage = $config;
    }

    /**
     * @param array $config
     * @return Config
     */
    public static function instance($config = [])
    {
        if (is_null(static::$instance)) {
            if ($config instanceof Config) {
                static::$instance = $config;
            } else {
                static::$instance = new static($config);
            }
        }
        return static::$instance;
    }

    private function loadLayout()
    {
        $default = new Finder();
        $default->files()->in(BP . 'app/layout/default')->name('*.yml');
        $files = [];
        $parser = new Parser();
        $config = [[], []];
        foreach ($default as $file) {
            $files[$file->getFilename()] = $file;
        }
        $bak = $files;
        foreach ([$this->offsetGet('theme/global/layout'), $this->offsetGet('theme/global/mobile_layout')] as $key => $theme) {
            $files = $bak;
            if ($theme !== 'default' && is_dir(BP . 'app/layout/' . $theme)) {
                $finder = new Finder();
                $finder->files()->in(BP . 'app/layout/' . $theme)->name('*.yml');
                foreach ($finder as $file) {
                    $files[$file->getFilename()] = $file;
                }
            }
            foreach ($files as $file) {
                try {
                    $array = $parser->parse($file->getContents());
                } catch (ParseException $e) {
                    throw new ParseException($e->getMessage() . ' File: ' . $file->getRealPath());
                }
                if ($array) {
                    $config[$key] = $this->arrayMerge($config[$key], $array);
                }
            }
        }
        list($result['pc'], $result['mobile']) = $config;
        return ['layout' => $result];
    }

    /**
     * @return array
     */
    private function loadFromYaml($type = '*')
    {
        if ($type !== '*') {
            $cache = $this->getContainer()->get('cache');
            $config = $cache->fetch($type, 'SYSTEM_CONFIG');
        }
        if (empty($config) && !is_array($config)) {
            if ($type === 'layout') {
                $config = $this->loadLayout();
            } else {
                $finder = new Finder();
                $finder->files()->in(BP . 'app')->exclude('layout')->name($type . '.yml');
                if ($this->bannedYml) {
                    $finder->notName($this->bannedYml);
                }
                $parser = new Parser();
                $config = $type !== '*' ? [] : [$type => []];
                foreach ($finder as $file) {
                    $key = $type !== '*' ? str_replace('.yml', '', $file->getFilename()) : $type;
                    if (!isset($config[$key])) {
                        $config[$key] = [];
                    }
                    try {
                        $array = $parser->parse($file->getContents());
                    } catch (ParseException $e) {
                        throw new ParseException($e->getMessage() . ' File: ' . $file->getRealPath());
                    }
                    if ($array) {
                        $config[$key] = $this->arrayMerge($config[$key], $array);
                    }
                }
            }
            $this->storage = $this->arrayMerge($this->storage, $config);
            if ($type !== '*') {
                $cache->save($type, $this->storage[$type] ?? [], 'SYSTEM_CONFIG');
            }
        } else {
            $this->storage = $this->arrayMerge($this->storage, [$type => $config]);
        }
        return $config;
    }

    /**
     * @return array
     */
    public function loadFromDB()
    {
        $tableGateway = $this->getTableGateway('core_config');
        $result = $tableGateway->select()->toArray();
        $config = [];
        foreach ($result as $item) {
            $value = [];
            if (!is_null($item['store_id'])) {
                $value['s' . $item['store_id']] = $item['value'];
            } else {
                $value['m' . $item['merchant_id']] = $item['value'];
            }
            $config = $this->arrayMerge($config, $this->generateConfig(explode('/', $item['path']), $value));
        }
        $this->storage['db'] = $config;
        return $config;
    }

    /**
     * Generate path array
     *
     * @param string|array $path
     * @return array
     */
    private function generateConfig($path, $value)
    {
        if (count($path) > 1) {
            $key = array_shift($path);
            return [$key => $this->generateConfig($path, $value)];
        } else {
            return [$path[0] => $value];
        }
    }

    private function getConfigByScope(array $array, $scope = null)
    {
        if (empty($this->keys)) {
            $this->keys = [
                'l' => 'l' . Bootstrap::getLanguage()->getId(),
                's' => 's' . Bootstrap::getStore()->getId(),
                'm' => 'm' . Bootstrap::getMerchant()->getId()
            ];
        }
        return is_null($scope) ? ($array[$this->keys['s']] ?? ($array[$this->keys['m']] ?? $array)) : ($array[$scope] ?? ($array[$this->keys['m']] ?? $array));
    }

    private function getConfigByPath($path, $config = null, $scope = null)
    {
        if (count($path) > 1) {
            $key = array_shift($path);
            $config = is_null($config) ? ($this->storage['db'][$key] ?? null) :
                    (isset($config[$key]) ? $config[$key] : null);
            if (!is_null($config)) {
                return $this->getConfigByPath($path, $config, $scope);
            }
        } elseif (isset($config[$path[0]])) {
            return $this->getConfigByScope($config[$path[0]], $scope);
        } elseif (strpos($path[0], '[]')) {
            $path[0] = str_replace('[]', '', $path[0]);
            return !empty($this->getConfigByPath($path, $config, $scope)) ? explode(',', $this->getConfigByPath($path, $config, $scope)) : [];
        }
        return null;
    }

    private function getDefaultConfig($path, $config)
    {
        if (count($path) > 1) {
            $key = array_shift($path);
            $config = isset($config[$key]) ? $config[$key] : null;
            if (!is_null($config) && isset($config['children'])) {
                return $this->getDefaultConfig($path, $config['children']);
            }
        } elseif (isset($config[$path[0]]['default'])) {
            return $config[$path[0]]['default'];
        }
        return [];
    }

    public function &offsetGet($key): mixed
    {
        if (isset($this->cache[$key])) {
            $result = $this->cache[$key];
        } elseif (strpos($key, '/')) {
            $path = explode('/', trim($key, '/'));
            if (preg_match('/^[slm]\d+$/', $path[0])) {
                $scope = array_shift($path);
            }
            $result = $this->getConfigByPath($path, null, $scope ?? null);
            if (is_null($result)) {
                $result = $this->getDefaultConfig($path, $this->offsetGet('system'));
            }
            $this->cache[$key] = $result;
        } else {
            $result = parent::offsetGet($key);
            if (is_null($result)) {
                $result = $this->loadFromYaml($key);
            }
        }
        return $result;
    }

    public function offsetExists($key): bool
    {
        $result = parent::offsetExists($key);
        if (!$result) {
            $this->loadFromYaml($key);
        }
        return parent::offsetExists($key);
    }
}
