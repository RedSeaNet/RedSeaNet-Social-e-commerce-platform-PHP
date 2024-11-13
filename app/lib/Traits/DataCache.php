<?php

namespace Redseanet\Lib\Traits;

use Redseanet\Lib\Cache\DataCache as Handler;

/**
 * Handle cache for data
 */
trait DataCache
{
    public function getCacheObject()
    {
        return Handler::instance($this)->getCacheObject();
    }

    public function getDataCacheKey()
    {
        return Handler::instance($this)->getDataCacheKey();
    }

    public function setDataCacheKey($dataCacheKey)
    {
        Handler::instance($this)->setDataCacheKey($dataCacheKey);
    }

    protected function deleteCache($cacheKey)
    {
        Handler::instance($this)->deleteCache($cacheKey);
    }

    protected function fetchRow($id, $key = null, $cacheKey = null)
    {
        return Handler::instance($this)->fetchRow($id, $key, $cacheKey);
    }

    protected function fetchList($key, $cacheKey = null)
    {
        return Handler::instance($this)->fetchList($key, $cacheKey);
    }

    protected function flushRow($id, $data, $cacheKey, $key = null, $lifetime = 0)
    {
        Handler::instance($this)->flushRow($id, $data, $cacheKey, $key, $lifetime);
    }

    protected function flushList($cacheKey)
    {
        Handler::instance($this)->flushList($cacheKey);
    }

    protected function addCacheList($key, $list, $cacheKey, $lifetime = 0)
    {
        Handler::instance($this)->addCacheList($key, $list, $cacheKey, $lifetime);
    }

    protected function addCacheAlias($key, $id, $cacheKey, $lifetime = 0)
    {
        Handler::instance($this)->addCacheAlias($key, $id, $cacheKey, $lifetime);
    }
}
