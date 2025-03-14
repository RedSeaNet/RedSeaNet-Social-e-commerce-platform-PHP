<?php

namespace Redseanet\Lib\Cache;

use Redseanet\Lib\Stdlib\Singleton;

class DataCache implements Singleton
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * @var \Redseanet\Lib\Cache
     */
    protected $cacheObject = null;

    /**
     * @var array
     */
    protected static $cachedData = [];

    /**
     * @var string
     */
    protected $dataCacheKey = null;

    /**
     * @var DataCache
     */
    protected static $instance = null;

    private function __construct()
    {
        $this->cacheObject = $this->getContainer()->get('cache');
    }

    /**
     * @param object $src
     * @return DataCache
     */
    public static function instance($src = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        if (is_object($src) && is_callable([$src, 'getCacheKey'])) {
            self::$instance->setDataCacheKey($src->getCacheKey());
        }
        return self::$instance;
    }

    /**
     * @return string
     */
    public function getDataCacheKey()
    {
        return $this->dataCacheKey;
    }

    /**
     * @param string $dataCacheKey
     */
    public function setDataCacheKey($dataCacheKey)
    {
        $this->dataCacheKey = $dataCacheKey;
    }

    /**
     * Get cache object
     *
     * @return \Redseanet\Lib\Cache
     */
    public function getCacheObject()
    {
        return $this->cacheObject;
    }

    /**
     * Read cached data
     *
     * @param string $cacheKey
     */
    protected function readCache($cacheKey)
    {
        if (!isset(static::$cachedData[$cacheKey])) {
            static::$cachedData[$cacheKey] = $this->getCacheObject()->fetch($cacheKey, 'DATA_');
        }
        return static::$cachedData[$cacheKey];
    }

    /**
     * Write cached data
     *
     * @param string $cacheKey
     */
    protected function writeCache($cacheKey, $lifetime = 0)
    {
        if (isset(static::$cachedData[$cacheKey])) {
            $this->getCacheObject()->save($cacheKey, static::$cachedData[$cacheKey], 'DATA_', $lifetime);
        }
    }

    /**
     * Delete cached data
     *
     * @param string $cacheKey
     */
    public function deleteCache($cacheKey)
    {
        $this->getCacheObject()->delete($cacheKey, 'DATA_');
    }

    /**
     * Fetch a row data by key
     *
     * @param int|string $id
     * @param string $key
     * @param string $cacheKey
     * @return mixed
     */
    public function fetchRow($id, $key = null, $cacheKey = null)
    {
        if (!is_null($cacheKey)) {
            $this->setDataCacheKey($cacheKey);
        }
        if (is_object($id) || is_array($id)) {
            $id = $id['id'];
        }
        $cacheKey = $this->getDataCacheKey() . (is_null($key) ? '_ROW_' : '_KEY_' . $key . '_') . $id;
        $result = static::$cachedData[$cacheKey] ?? $this->readCache($cacheKey);
        if ($result && !is_null($key)) {
            $result = $this->fetchRow($result);
            if ($result === false) {
                $this->flushRow($id, null, $cacheKey, $key);
            }
        }
        return $result;
    }

    /**
     * Fetch row data by sql
     *
     * @param string $key
     * @param string $cacheKey
     * @return array
     */
    public function fetchList($key, $cacheKey = null)
    {
        if (!is_null($cacheKey)) {
            $this->setDataCacheKey($cacheKey);
        }
        $cacheKey = $this->getDataCacheKey() . '_LIST_' . $key;
        $result = static::$cachedData[$cacheKey] ?? $this->readCache($cacheKey);
        return $result;
    }

    /**
     * Add or update row data
     *
     * @param string $id
     * @param mixed $data
     * @param string $cacheKey
     * @param string $key
     */
    public function flushRow($id, $data, $cacheKey, $key = null, $lifetime = 0)
    {
        if (!is_null($cacheKey)) {
            $this->setDataCacheKey($cacheKey);
        }
        $cacheKey = $this->getDataCacheKey() . (is_null($key) ? '_ROW_' : '_KEY_' . $key . '_') . $id;
        if (is_null($data)) {
            $this->deleteCache($cacheKey);
        } else {
            static::$cachedData[$cacheKey] = $data;
            $this->writeCache($cacheKey, $lifetime);
        }
    }

    /**
     * Flush all list records
     *
     * @param string $cacheKey
     */
    public function flushList($cacheKey)
    {
        if (!is_null($cacheKey)) {
            $this->setDataCacheKey($cacheKey);
        }
        $cacheListKey = $this->getDataCacheKey() . '_LIST';
        $list = $this->readCache($cacheListKey);
        if ($list) {
            foreach ($list as $key => $value) {
                $this->deleteCache($key);
            }
            $this->deleteCache($cacheListKey);
        }
    }

    /**
     * Add or update a list record
     *
     * @param string $key
     * @param array $list
     * @param string $cacheKey
     */
    public function addCacheList($key, $list, $cacheKey, $lifetime = 0)
    {
        if (!is_null($cacheKey)) {
            $this->setDataCacheKey($cacheKey);
        }
        $cacheKey = $this->getDataCacheKey() . '_LIST_' . $key;
        static::$cachedData[$cacheKey] = $list;
        $this->writeCache($cacheKey);
        $cacheListKey = $this->getDataCacheKey() . '_LIST';
        if (!$this->readCache($cacheListKey)) {
            static::$cachedData[$cacheListKey] = [$cacheKey => 1];
        } else {
            static::$cachedData[$cacheListKey][$cacheKey] = 1;
        }
        $this->writeCache($cacheListKey, $lifetime);
    }

    public function addCacheAlias($key, $id, $cacheKey, $lifetime = 0)
    {
        if (!is_null($cacheKey)) {
            $this->setDataCacheKey($cacheKey);
        }
        $cacheKey = $this->getDataCacheKey() . '_ROW_' . $key;
        static::$cachedData[$cacheKey] = $id;
        $this->writeCache($cacheKey, $lifetime);
    }
}
