<?php

namespace Redseanet\Lib\Cache;

use Doctrine\Common\Cache as DoctrineCache;
use Memcache;
use Memcached;
use MongoClient;
use MongoCollection;
use MongoDB\Driver\Manager as MongoDBManager;
use MongoDB\Collection as MongoDBCollection;
use Predis\Client as Predis;
use Redis;
use SQLite3;

/**
 * Generate cache pool based on config array
 */
abstract class Factory
{
    /**
     * @var array Describe the minimal version of php extension
     */
    public static $EXTENSION_VERSION = [
        'apc' => ['version' => '3.0.0', 'name' => 'apc'], // deprecated
        'apcu' => ['version' => '4.0.7', 'name' => 'apcu'],
        'memcache' => ['version' => '2.0.0', 'name' => 'memcache'],
        'memcached' => ['version' => '2.0.0', 'name' => 'memcached'],
        'mongo' => ['version' => '1.3.0', 'name' => 'mongo'], // deprecated
        'mongodb' => ['version' => '1.1.0', 'name' => 'mongodb'],
        'redis' => ['version' => '2.2.7', 'name' => 'redis'],
        'wincache' => ['version' => '1.3.5.0', 'name' => 'wincache']
    ];

    /**
     * @param array $config
     * @return mixed
     * @throws \UnexpectedValueException
     */
    public static function getCachePool($config)
    {
        try {
            if (isset($config['adapter']) && static::checkExtension($config['adapter'])) {
                $method = 'prepare' . $config['adapter'];
                $class = 'Doctrine\\Common\\Cache\\' . $config['adapter'] . 'Cache';
                if (is_callable(__CLASS__ . '::' . $method)) {
                    return static::$method($config);
                } elseif (class_exists($class)) {
                    return new $class();
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return static::prepareFilesystem($config);
    }

    /**
     * @param string $name
     * @return boolean
     */
    private static function checkExtension($name)
    {
        $name = strtolower($name);
        if (isset(static::$EXTENSION_VERSION[$name])) {
            return extension_loaded(static::$EXTENSION_VERSION[$name]['name']) && version_compare(phpversion(static::$EXTENSION_VERSION[$name]['name']), static::$EXTENSION_VERSION[$name]['version'], '>=');
        }
        return true;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return DoctrineCache\MemcacheCache
     */
    private static function prepareMemcache($config)
    {
        $client = new Memcache();
        if (isset($config['host'])) {
            $host = $config['host'];
            $port = 11211;
        } elseif (isset($config['socket'])) {
            $host = $config['socket'];
            $port = 0;
        }
        $client->addServer($host, $port);
        $pool = new DoctrineCache\MemcacheCache();
        $pool->setMemcache($client);
        return $pool;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return DoctrineCache\MemcachedCache
     */
    private static function prepareMemcached($config)
    {
        $client = new Memcached();
        if (isset($config['host'])) {
            $host = $client['host'];
            $port = 11211;
        } elseif (isset($config['socket'])) {
            $host = $config['socket'];
            $port = 0;
        }
        $client->addServer($host, $port);
        $pool = new DoctrineCache\MemcachedCache();
        $pool->setMemcache($client);
        return $pool;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return DoctrineCache\RedisCache
     */
    private static function prepareRedis($config)
    {
        $client = new Redis();
        $client->connect(($config['host'] ?? 'localhost'), ($config['port'] ?? 6379));
        $client->select((int) ($config['db'] ?? 0));
        $pool = new DoctrineCache\RedisCache();
        $pool->setRedis($client);
        return $pool;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return DoctrineCache\PredisCache
     */
    private static function preparePredis($config)
    {
        $server = [
            'scheme' => $config['scheme'] ?? 'tcp',
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 6379,
            'database' => (int) ($config['db'] ?? 0)
        ];
        $client = new Predis($server);
        $pool = new DoctrineCache\PredisCache($client);
        return $pool;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return DoctrineCache\FilesystemCache
     */
    private static function prepareFilesystem($config)
    {
        $pool = new DoctrineCache\FilesystemCache(($config['directory'] ?? BP . 'var/cache'), '.dat', 0);
        return $pool;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return DoctrineCache\PHPFileCache
     */
    private static function preparePHPFile($config)
    {
        $pool = new DoctrineCache\PHPFileCache(($config['directory'] ?? BP . 'var/cache'), '.php', 0);
        return $pool;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return DoctrineCache\MongoDBCache
     */
    private static function prepareMongo($config)
    {
        $server = 'mongodb://localhost:27017';
        if (isset($config['server'])) {
            $server = $config['server'];
            unset($config['server']);
        } elseif (isset($config['host'])) {
            $server = 'mongodb://' . $config['host'] . ($config['port'] ?? 27017);
            unset($config['host'], $config['port']);
        }
        $db = $config['db'] ?? 'Redseanet';
        unset($config['db']);
        $client = new MongoClient($server, $config);
        $collection = new MongoCollection($client->selectDB($db), 'cache');
        $pool = new DoctrineCache\MongoDBCache($collection);
        return $pool;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return MongoDBCache
     */
    private static function prepareMongoDB($config)
    {
        $server = 'mongodb://localhost:27017';
        if (isset($config['server'])) {
            $server = $config['server'];
            unset($config['server']);
        } else {
            if (isset($config['username'])) {
                $auth = $config['username'] . (isset($config['password']) ? ':' . $config['password'] : '') . '@';
                unset($config['username'], $config['password']);
            } elseif (isset($config['user'])) {
                $auth = $config['user'] . (isset($config['pass']) ? ':' . $config['pass'] : '') . '@';
                unset($config['user'], $config['pass']);
            } else {
                $auth = '';
            }
            if (isset($config['host'])) {
                $server = 'mongodb://' . $auth . $config['host'] . ($config['port'] ?? 27017);
                unset($config['host'], $config['port']);
            } elseif (isset($config['socket'])) {
                $server = 'mongodb://' . $auth . $config['socket'];
                unset($config['socket']);
            }
        }

        $db = $config['db'] ?? 'Redseanet';
        unset($config['db']);
        $manager = new MongoDBManager($server, $config);
        $collection = new MongoDBCollection($manager, $db, 'cache');
        $pool = new MongoDBCache($collection);
        return $pool;
    }

    /**
     * @param array|\ArrayAccess $config
     * @return DoctrineCache\SQLite3Cache
     */
    private static function prepareSQLite3($config)
    {
        $client = new SQLite3($config['filename'] ?? BP . 'var/cache/sqlite3.dat');
        $pool = new DoctrineCache\SQLite3Cache($client, 'cache');
        return $pool;
    }
}
