<?php

namespace Redseanet\Lib\Model;

use BadMethodCallException;
use Exception;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Sql;
use Laminas\Db\Sql\Select;
use Redseanet\Lib\Stdlib\ArrayObject;

/**
 * Data operator for collection model
 *
 * @uses Select
 * @method Select columns(array $columns, bool  $prefixColumnsWithTable)
 * @method Select join(string|array $name, string $on, string|array $columns, string $type)
 * @method Select where(Sql\Where|\Closure|string|array|Sql\Predicate\PredicateInterface $predicate, string $combination)
 * @method Select group(array|string $group)
 * @method Select having(Sql\Where|\Closure|string|array $predicate, , string $combination)
 * @method Select order(string|array $order)
 * @method Select limit(int $limit)
 * @method Select offset(int $offset)
 * @method Select combine(Select $select, string $type, string $modifier)
 * @method string getRawState(null|string $key)
 */
abstract class AbstractCollection extends ArrayObject
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    /**
     * @var Select
     */
    protected $select = null;

    /**
     * @var \Redseanet\Lib\EventDispatcher
     */
    protected $eventDispatcher = null;
    protected $cacheKey = '';
    protected $isLoaded = false;
    protected $arrayMode = false;
    protected $tableName = '';
    protected $cacheLifeTime = 0;

    public function __construct()
    {
        $this->construct();
    }

    /**
     * Overwrite normal method instead of magic method
     */
    abstract protected function construct();

    public function __call($name, $arguments)
    {
        if (is_callable([$this->select, $name])) {
            $this->isLoaded = false;
            return call_user_func_array([$this->select, $name], $arguments);
        } else {
            throw new BadMethodCallException('Call to undefined method: ' . $name);
        }
    }

    public function __clone()
    {
        $this->select = clone $this->select;
        $this->isLoaded = false;
    }

    /**
     * Data operator initialization
     *
     * @param string $table
     */
    protected function init($table)
    {
        $this->tableName = $table;
        $this->getTableGateway($table);
        $this->cacheKey = $table;
        if (is_null($this->select)) {
            $this->select = $this->getTableGateway($this->tableName)->getSql()->select();
        }
    }

    /**
     * Get Select instance
     *
     * @return Select
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * Get cache key
     *
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * Load data
     *
     * @param bool $useCache
     * @param bool $arrayMode
     * @return AbstractCollection
     */
    public function load($useCache = true, $arrayMode = null)
    {
        if (is_bool($arrayMode)) {
            $this->arrayMode = $arrayMode;
        }
        if (!$this->isLoaded) {
            try {
                if ($useCache) {
                    $cacheKey = md5($this->select->getSqlString($this->getTableGateway($this->tableName)->getAdapter()->getPlatform()));
                    $result = $this->fetchList($cacheKey, $this->getCacheKey());
                } else {
                    $result = false;
                }
                if (!is_array($result) && empty($result)) {
                    $this->beforeLoad();
                    $result = $this->getTableGateway($this->tableName)->selectWith($this->select)->toArray();
                    if ($useCache) {
                        $this->addCacheList($cacheKey, $result, $this->getCacheKey(), $this->cacheLifeTime);
                    }
                    if (count($result)) {
                        $this->afterLoad($result);
                    }
                } else {
                    $this->afterLoad($result);
                }
            } catch (InvalidQueryException $e) {
                $this->getContainer()->get('log')->logException($e);
                throw $e;
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                throw $e;
            }
        }
        return $this;
    }

    /**
     * Walk collection
     *
     * @param callable $callback
     */
    public function walk(callable $callback, ...$params)
    {
        if (!$this->isLoaded) {
            if ($this->select->getRawState('limit')) {
                $this->load();
                array_walk($this->storage, $callback, $params);
            } else {
                $select = clone $this->select;
                $this->select->limit(20);
                $offset = 0;
                while (1) {
                    $this->storage = [];
                    $this->select->offset($offset);
                    $offset += 20;
                    $this->isLoaded = false;
                    $this->load();
                    if (count($this->storage)) {
                        array_walk($this->storage, $callback, $params);
                    } else {
                        break;
                    }
                }
                $this->isLoaded = false;
                $this->select = $select;
            }
        } else {
            array_walk($this->storage, $callback, $params);
        }
    }

    /**
     * Get event dispatcher object
     *
     * @return \Redseanet\Lib\EventDispatcher
     */
    protected function getEventDispatcher()
    {
        if (is_null($this->eventDispatcher)) {
            $this->eventDispatcher = $this->getContainer()->get('eventDispatcher');
        }
        return $this->eventDispatcher;
    }

    /**
     * Event before load data
     */
    protected function beforeLoad()
    {
        $this->getEventDispatcher()->trigger(get_class($this) . '.collection.load.before', ['collection' => $this]);
    }

    /**
     * Event after load cache
     */
    protected function afterLoad(&$result)
    {
        $this->isLoaded = true;
        $className = str_replace('\\Collection', '', get_class($this));
        if (!$this->arrayMode && class_exists($className)) {
            foreach ($result as &$item) {
                if (is_array($item)) {
                    $object = new $className();
                    $object->setData($item);
                    $item = $object;
                } else {
                    break;
                }
            }
        }
        $this->storage = $result;
        $this->getEventDispatcher()->trigger(get_class($this) . '.collection.load.after', ['collection' => $this]);
    }

    public function jsonSerialize(): mixed
    {
        if (!$this->isLoaded) {
            $this->load();
        }
        return parent::jsonSerialize();
    }

    public function serialize()
    {
        $storage = $this->storage;
        if (!$this->arrayMode) {
            foreach ($this->storage as &$item) {
                if (is_object($item)) {
                    $item = $item->toArray();
                } else {
                    break;
                }
            }
        }
        $result = parent::serialize();
        $this->storage = $storage;
        return $result;
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        foreach ($data as $key => $value) {
            if ($key === 'storage' && !$this->arrayMode) {
                $className = str_replace('\\Collection', '', get_class($this));
                if (class_exists($className)) {
                    foreach ($value as &$item) {
                        if (is_array($item)) {
                            $object = new $className();
                            $object->setData($item);
                            $item = $object;
                        } else {
                            break;
                        }
                    }
                }
            } else {
                $this->$key = $value;
            }
        }
        if ($this instanceof Singleton) {
            static::$instance = $this;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getArrayCopy()
    {
        if (!$this->isLoaded) {
            $this->load();
        }
        return parent::getArrayCopy();
    }

    /**
     * {@inheritdoc}
     */
    public function &__get($key)
    {
        if (!$this->isLoaded) {
            $this->load();
        }
        return parent::__get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function &offsetGet($key): mixed
    {
        if (!$this->isLoaded) {
            $this->load();
        }
        return parent::offsetGet($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        if (!$this->isLoaded) {
            $this->load();
        }
        return parent::getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key): void
    {
        if (!$this->isLoaded) {
            $this->load();
        }
        parent::offsetUnset($key);
    }

    /**
     * {@inheritdoc}
     */
    public function __unset($key)
    {
        if (!$this->isLoaded) {
            $this->load();
        }
        return parent::__unset($key);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (!$this->isLoaded) {
            $this->load();
        }
        return parent::count();
    }

    /**
     * Reset part of select object
     *
     * @param string $part
     * @return Select
     */
    public function reset($part)
    {
        $this->isLoaded = false;
        $this->storage = [];
        return $this->select->reset($part);
    }

    /**
     * Create "IN" predicate
     *
     * Utilizes In predicate
     *
     * @param string $identifier
     * @param array|Select|AbstractCollection $valueSet
     * @return Select
     */
    public function in($identifier, $valueSet)
    {
        if ($valueSet instanceof AbstractCollection) {
            $valueSet = $valueSet->getSelect();
        }
        $this->select->where->in($identifier, $valueSet);
        return $this->select;
    }

    /**
     * Create "IN" predicate
     *
     * Utilizes In predicate
     *
     * @param string $identifier
     * @param array|Select|AbstractCollection $valueSet
     * @return Select
     */
    public function notIn($identifier, $valueSet)
    {
        if ($valueSet instanceof AbstractCollection) {
            $valueSet = $valueSet->getSelect();
        }
        $this->select->where->notIn($identifier, $valueSet);
        return $this->select;
    }

    /**
     * Get SQL string for statement
     *
     * @param null|PlatformInterface $adapterPlatform
     *
     * @return string
     */
    public function getSqlString(PlatformInterface $adapterPlatform = null)
    {
        if (is_null($adapterPlatform)) {
            $adapterPlatform = $this->getContainer()->get('dbAdapter')->getPlatform();
        }
        return $this->select->getSqlString($adapterPlatform);
    }
}
