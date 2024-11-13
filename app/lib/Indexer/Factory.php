<?php

namespace Redseanet\Lib\Indexer;

use MongoDB\Driver\Manager as MongoDBManager;

/**
 * Indexer handler factory
 */
abstract class Factory
{
    /**
     * Get handler based on configuration
     *
     * @param array $config
     * @param string $entityType
     * @return Handler\AbstractHandler
     */
    public static function getHandler($config, $entityType)
    {
        try {
            if (isset($config['adapter'])) {
                $method = 'prepare' . $config['adapter'];
                if (is_callable(__CLASS__ . '::' . $method)) {
                    $handler = static::$method($config, $entityType);
                }
            }
            if (empty($handler)) {
                $handler = static::prepareDatabase($entityType);
            }
            return $handler;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return null;
    }

    /**
     * Get database handler
     *
     * @param string $entityType
     * @return Handler\Database
     */
    private static function prepareDatabase($entityType)
    {
        return new Handler\Database($entityType);
    }

    /**
     * Get mongodb handler
     *
     * @param array $config
     * @param string $entityType
     * @return Handler\MongoDB
     */
    private static function prepareMongoDB($config, $entityType)
    {
        if (extension_loaded('mongodb') && version_compare(phpversion('mongodb'), '1.1.0', '>=')) {
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
            return new Handler\MongoDB($manager, $db, $entityType);
        }
        return false;
    }
}
