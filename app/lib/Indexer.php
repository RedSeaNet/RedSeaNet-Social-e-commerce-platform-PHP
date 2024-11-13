<?php

namespace Redseanet\Lib;

use Redseanet\Lib\Indexer\Factory;

/**
 * Indexer manager
 */
class Indexer implements Stdlib\Singleton
{
    use \Redseanet\Lib\Traits\Container;

    protected static $cache = null;
    protected $config = null;
    protected $handler = [];
    protected static $instance = null;

    private function __construct($config)
    {
        if ($config instanceof Container) {
            $this->setContainer($config);
            $config = [];
        }
        if (empty($config)) {
            $adapterObject = $this->getContainer()->get('config')['adapter'];
            $config = $adapterObject['indexer'] ?? [];
        }
        $this->config = $config;
    }

    public static function instance($config = [])
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($config);
        }
        return static::$instance;
    }

    /**
     * Get cache handler
     *
     * @return Cache
     */
    protected function getCacheInstance()
    {
        if (is_null(static::$cache)) {
            static::$cache = $this->getContainer()->get('cache');
        }
        return static::$cache;
    }

    /**
     * Get handler based on entity type
     *
     * @param string $entityType
     * @return Indexer\Handler\AbstractHandler
     */
    public function getHandler($entityType)
    {
        if (!isset($this->handler[$entityType])) {
            $this->handler[$entityType] = Factory::getHandler($this->config, $entityType);
        }
        return $this->handler[$entityType];
    }

    /**
     * Delete data from indexer
     *
     * @param string $entityType
     * @param int $languageId
     * @param array $constraint
     */
    public function delete($entityType, $languageId, $constraint = [])
    {
        $this->getHandler($entityType)->delete($languageId, $constraint);
        $this->getCacheInstance()->delete('', 'INDEX_');
    }

    /**
     * Insert data into indexer
     *
     * @param string $entityType
     * @param int $languageId
     * @param array $values
     */
    public function insert($entityType, $languageId, $values)
    {
        $this->getHandler($entityType)->insert($languageId, $values);
        $this->getCacheInstance()->delete('', 'INDEX_');
    }

    /**
     * Reindex indexer
     *
     * @param string $entityType
     */
    public function reindex($entityType)
    {
        $this->getHandler($entityType)->reindex();
        $this->getCacheInstance()->delete('', 'INDEX_');
    }

    /**
     * Replace data into indexer
     *
     * @param string $entityType
     * @param int $languageId
     * @param array $values
     * @param array $constraint
     */
    public function replace($entityType, $languageId, $values, $constraint)
    {
        $this->delete($entityType, $languageId, $constraint);
        foreach ($values as $item) {
            $this->insert($entityType, $languageId, $item + $constraint);
        }
        $this->getCacheInstance()->delete('', 'INDEX_');
    }

    /**
     * Select data from indexer
     *
     * @param string $entityType
     * @param int $languageId
     * @param array $constraint
     * @param array $options
     * @return array
     */
    public function select($entityType, $languageId, $constraint = [], $options = [])
    {
        $key = md5($entityType . $languageId .
                ($constraint instanceof \Laminas\Db\Sql\Select ?
                $constraint->getSqlString($this->getContainer()->get('dbAdapter')->getPlatform()) :
                serialize($constraint)) .
                serialize($options));
        $result = empty($options['noCache']) ?
                $this->getCacheInstance()->fetch($key, 'INDEX_') : 0;
        unset($options['noCache']);
        if (empty($result) && !is_array($result)) {
            $result = $this->getHandler($entityType)->select($languageId, $constraint, $options);
            $this->getCacheInstance()->save($key, $result, 'INDEX_');
        }
        return $result;
    }

    /**
     * Update data of indexer
     *
     * @param string $entityType
     * @param int $languageId
     * @param array $values
     * @param array $constraint
     */
    public function update($entityType, $languageId, $values, $constraint = [])
    {
        $this->getHandler($entityType)->update($languageId, $values, $constraint);
        $this->getCacheInstance()->delete('', 'INDEX_');
    }

    /**
     * Insert/Update data of indexer
     *
     * @param string $entityType
     * @param int $languageId
     * @param array $values
     * @param array $constraint
     */
    public function upsert($entityType, $languageId, $values, $constraint = [])
    {
        $this->getHandler($entityType)->upsert($languageId, $values, $constraint);
        $this->getCacheInstance()->delete('', 'INDEX_');
    }
}
