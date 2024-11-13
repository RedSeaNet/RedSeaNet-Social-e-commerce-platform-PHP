<?php

namespace Redseanet\Lib;

use ArrayAccess;
use BadMethodCallException;
use Exception;
use Doctrine\Common\Cache\CacheProvider;
use Redseanet\Lib\Cache\Factory;
use Redseanet\Lib\Stdlib\Singleton;

/**
 * Handle cache operation by using Doctrine\Cache pool
 *
 * @method array|null getStats()
 */
final class Cache implements ArrayAccess, Singleton
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * @var Cache
     */
    protected static $instance = null;

    /**
     * @var CacheProvider[]
     */
    private $pool = [];

    /**
     * @var array
     */
    private $unhitPrefix = [];

    /**
     * @var array
     */
    private $persistentPrefix = [];

    /**
     * @var string
     */
    public $salt = '';

    /**
     * @var callable
     */
    private $decoder;

    /**
     * @var callable
     */
    private $encoder;

    /**
     * @var bool
     */
    private $disabled;

    /**
     * @var array
     */
    private $remote = [];

    /**
     * @param array|Container $config
     * @throws \UnexpectedValueException
     */
    private function __construct($config = [])
    {
        if ($config instanceof Container) {
            $this->setContainer($config);
            $config = [];
        }
        if (!empty($config['multipool'])) {
            foreach ((array) $config['multipool'] as $prefix => $pool) {
                $this->pool[$prefix] = Factory::getCachePool($pool);
            }
            unset($config['multipool']);
        }
        $this->pool['default'] = Factory::getCachePool($config);
        if (isset($config['persistent'])) {
            $this->persistentPrefix = (array) $config['persistent'];
        }
        if (isset($config['salt'])) {
            $this->salt = $config['salt'];
        }
        if (isset($config['remote'])) {
            $this->remote = (array) $config['remote'];
        }
        $this->disabled = (bool) ($config['disabled'] ?? false);
        if (!isset($config['compress']) || $config['compress']) {
            $this->encoder = function ($data) {
                return gzencode(serialize($data));
            };
            $this->decoder = function ($data) {
                return unserialize(@gzdecode($data));
            };
        } else {
            $this->encoder = function ($data) {
                return serialize($data);
            };
            $this->decoder = function ($data) {
                return unserialize($data);
            };
        }
    }

    private function getPool($prefix = '')
    {
        return $this->pool[$prefix ?: 'default'] ?? $this->pool['default'];
    }

    /**
     * Call pool method
     * @param string $name
     * @param array $arguments
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->getPool(), $name])) {
            return call_user_func_array([$this->getPool(), $name], $arguments);
        } else {
            throw new BadMethodCallException('Call to undefined method: ' . $name);
        }
    }

    /**
     * @param array $config
     * @return Cache
     */
    public static function instance($config = [])
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($config);
        }
        return static::$instance;
    }

    /**
     * @uses CacheProvider::contains
     * @param string $id
     * @return bool
     */
    public function contains($id)
    {
        return $this->getPool()->contains($id);
    }

    /**
     * @uses CacheProvider::delete
     * @param string $id
     * @param string $prefix
     * @param bool $remote
     * @return bool
     */
    public function delete($id = '', $prefix = '', $remote = true)
    {
        $pool = $this->getPool($prefix);
        if ($prefix) {
            if (in_array($prefix, $this->persistentPrefix)) {
                return false;
            }
            $list = $pool->fetch($this->salt . 'CACHE_LIST_' . $prefix);
            if ($list) {
                $list = call_user_func($this->decoder, $list);
                unset($list[$id]);
            } else {
                $list = [];
            }
            $pool->save($this->salt . 'CACHE_LIST_' . $prefix, call_user_func($this->encoder, $list));
            if (empty($list)) {
                $list = $pool->fetch($this->salt . 'CACHE_LIST');
                if ($list) {
                    $list = call_user_func($this->decoder, $list);
                    unset($list[$prefix]);
                } else {
                    $list = [];
                }
                $pool->save($this->salt . 'CACHE_LIST', call_user_func($this->encoder, $list));
            } elseif (!$id) {
                foreach ($list as $key => $value) {
                    $pool->delete($this->salt . $prefix . $key);
                }
            }
        }
        $result = $id ? $pool->delete($this->salt . $prefix . $id) : true;
        if ($remote) {
            try {
                $data = json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'flushCache',
                    'params' => [$id, $prefix]
                ]);
                foreach ($this->remote as $client) {
                    $client = curl_init($client);
                    curl_setopt($client, CURLOPT_POST, 1);
                    curl_setopt($client, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Content-Length: ' . strlen($data)
                    ]);
                    curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($client, CURLOPT_POSTFIELDS, $data);
                    curl_exec($client);
                    curl_close($client);
                }
            } catch (Exception $e) {
            }
        }
        return $result;
    }

    /**
     * @uses CacheProvider::fetch
     * @param string $id
     * @param string $prefix
     * @return mixed
     */
    public function fetch($id, $prefix = '')
    {
        if ($this->disabled || count($this->unhitPrefix) && in_array($prefix, $this->unhitPrefix)) {
            $result = null;
        } else {
            $result = call_user_func($this->decoder, $this->getPool($prefix)->fetch($this->salt . $prefix . $id));
        }
        if (Bootstrap::isDeveloperMode()) {
            $this->getContainer()->get('eventDispatcher')->trigger('cache.fetch.after', ['key' => $id, 'prefix' => $prefix, 'result' => $result]);
        }
        return $result;
    }

    /**
     * @return boolean
     */
    public function flushAll()
    {
        array_walk($this->pool, function ($item) {
            $item->flushAll();
        });
        return true;
    }

    /**
     * @return boolean
     */
    public function deleteAll()
    {
        array_walk($this->pool, function ($item) {
            $item->deleteAll();
        });
        return true;
    }

    /**
     * Set prefix of unhit cache key
     *
     * @param string $prefix
     * @return Cache
     */
    public function unhit($prefix)
    {
        $this->unhitPrefix[] = $prefix;
        return $this;
    }

    /**
     * @uses CacheProvider::save
     * @param string $id
     * @param mixed $data
     * @param string $prefix
     * @param int $lifeTime
     * @return bool
     */
    public function save($id, $data, $prefix = '', $lifeTime = 0)
    {
        if ($this->disabled) {
            return true;
        }
        if ($prefix) {
            $list = $this->getPool($prefix)->fetch($this->salt . 'CACHE_LIST_' . $prefix);
            if ($list) {
                $list = call_user_func($this->decoder, $list);
            } else {
                $list = [];
            }
            if (!isset($list[$id])) {
                $list[$id] = $lifeTime ? time() + $lifeTime : -1;
                $this->getPool($prefix)->save($this->salt . 'CACHE_LIST_' . $prefix, call_user_func($this->encoder, $list));
            }
            $list = $this->getPool($prefix)->fetch($this->salt . 'CACHE_LIST');
            if ($list) {
                $list = call_user_func($this->decoder, $list);
            } else {
                $list = [];
            }
            if (!isset($list[$prefix])) {
                $list[$prefix] = 1;
                $this->getPool($prefix)->save($this->salt . 'CACHE_LIST', call_user_func($this->encoder, $list));
            }
        }
        return $this->getPool($prefix)->save($this->salt . $prefix . $id, call_user_func($this->encoder, $data), $lifeTime);
    }

    /**
     * @uses CacheProvider::fetchMultiple
     * @param array $keys
     * @return array
     */
    public function fetchMultiple(array $keys, $prefix = '')
    {
        $result = [];
        if (!$this->disabled) {
            $fetchKey = $keys;
            if (count($this->unhitPrefix)) {
                $fetchKey = [];
                $regex = '/^(' . implode('|', $this->unhitPrefix) . ')/';
                foreach ($keys as $key) {
                    if (!preg_match($regex, $key)) {
                        $fetchKey[] = $this->salt . $key;
                    }
                }
            }
            $values = $this->getPool($prefix)->fetchMultiple($fetchKey);
            foreach ($values as $value) {
                $result[] = call_user_func($this->decoder, $value);
            }
            $result = array_combine($fetchKey, $result);
        }
        if (Bootstrap::isDeveloperMode()) {
            $this->getContainer()->get('eventDispatcher')->trigger('cache.fetchMultiple.after', ['keys' => $keys, 'prefix' => $prefix, 'result' => $result]);
        }
        return $result;
    }

    /**
     * @uses CacheProvider::saveMultiple
     * @param array $keys
     * @param int $lifetime
     * @return bool
     */
    public function saveMultiple(array $keysAndValues, $lifetime = 0)
    {
        if ($this->disabled) {
            return true;
        }
        $pairs = [];
        foreach ($keysAndValues as $key => $value) {
            $pairs[$this->salt . $key] = call_user_func($this->encoder, $value);
        }
        return $this->getPool()->saveMultiple($pairs, $lifetime);
    }

    public function offsetExists($offset): bool
    {
        if ($this->contains($offset)) {
            return true;
        } else {
            return false;
        }
    }

    public function &offsetGet(mixed $offset): mixed
    {
        return $this->fetch($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->save($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->delete($offset);
    }

    public function getAllKeys()
    {
        $result = [];
        foreach ($this->pool as $pool) {
            $result[] = $pool->keys('*');
        }
        return $result;
    }
}
