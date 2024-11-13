<?php

namespace Redseanet\Lib\Model;

use Exception;
use Redseanet\Lib\Stdlib\ArrayObject;
use Traversable;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Metadata\Source\Factory;

/**
 * Data operator for single model
 */
abstract class AbstractModel extends ArrayObject
{
    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;

    protected $columns = [];
    protected $updatedColumns = [];
    protected $primaryKey = 'id';
    protected $isNew = true;
    protected $isLoaded = false;
    protected $cacheKey = '';
    protected $eventDispatcher = null;
    protected $tableName = '';
    protected $catchLifeTime = 0;

    public function __construct($input = [])
    {
        $this->setData($input);
        $this->construct();
    }

    public function __clone()
    {
        $this->isLoaded = false;
    }

    /**
     * Overwrite normal method instead of magic method
     */
    abstract protected function construct();

    /**
     * Data operator initialization
     *
     * @param string $table         Table name
     * @param string $primaryKey    Primary key name
     * @param array $columns        Table columns
     */
    protected function init($table, $primaryKey = 'id', $columns = [])
    {
        $this->tableName = $table;
        $this->getTableGateway($table);
        $this->cacheKey = $table;
        $this->columns = $columns;
        $this->primaryKey = $primaryKey;
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
     * Get primary key value
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->storage[$this->primaryKey] ?? null;
    }

    /**
     * Get primary key name
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Set primary key value
     *
     * @param int|string $id
     * @return AbstractModel
     */
    public function setId($id)
    {
        if (is_null($id)) {
            $this->isNew = true;
        }
        $this->storage[$this->primaryKey] = $id;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->updatedColumns[$key] = 1;
        parent::offsetSet($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key): void
    {
        unset($this->updatedColumns[$key]);
        parent::offsetUnset($key);
    }

    /**
     * Set the value at the specified key to value
     *
     * @param string|array $key
     * @param mixed $value
     * @return AbstractModel
     */
    public function setData($key, $value = null)
    {
        if (is_array($key) || $key instanceof Traversable) {
            foreach ($key as $k => $v) {
                $this->offsetSet($k, $v);
            }
        } else {
            $this->offsetSet($key, $value);
        }
        return $this;
    }

    /**
     * Load data
     *
     * @param int|string|array $id    Primary key value by default
     * @param string $key
     * @return AbstractModel
     * @throws InvalidQueryException
     */
    public function load($id, $key = null)
    {
        if (!$this->isLoaded) {
            try {
                if (is_array($id)) {
                    $result = $this->fetchRow(json_encode($id), null, $this->getCacheKey());
                } elseif (is_null($key) || $key === $this->primaryKey) {
                    $key = $this->primaryKey;
                    $result = $this->fetchRow($id, null, $this->getCacheKey());
                } else {
                    $result = $this->fetchRow($id, $key, $this->getCacheKey());
                }
                if (!$result) {
                    $select = $this->getTableGateway($this->tableName)->getSql()->select();
                    $select->where(is_array($id) ? $id : [(in_array($key, $this->getColumns()) ? $this->tableName . '.' . $key : $key) => $id]);
                    $this->beforeLoad($select);
                    $result = $this->getTableGateway($this->tableName)->selectWith($select)->toArray();
                    if (count($result)) {
                        $this->afterLoad($result);
                        if (is_array($id)) {
                            $this->flushRow(json_encode($id), $this->storage, $this->getCacheKey(), $this->catchLifeTime);
                        } else {
                            $this->flushRow($this->storage[$this->primaryKey], $this->storage, $this->getCacheKey(), $this->catchLifeTime);
                            if ($key !== $this->primaryKey) {
                                $this->addCacheAlias($key . '=' . $id, $this->storage[$this->primaryKey], $this->getCacheKey());
                            }
                        }
                    }
                } else {
                    $this->afterLoad($result);
                }
            } catch (InvalidQueryException $e) {
                $this->getContainer()->get('log')->logException($e);
                if ($this->transaction) {
                    $this->rollback();
                }
                throw $e;
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                throw $e;
            }
        }
        return $this;
    }

    /**
     * Is update or insert
     *
     * @param array $constraint
     * @param bool $insertForce
     * @return bool
     */
    protected function isUpdate($constraint = [], $insertForce = false)
    {
        return !$insertForce && (!empty($constraint) || $this->getId());
    }

    /**
     * Insert/Update data
     *
     * @param array $constraint
     * @param bool $insertForce
     * @return AbstractModel
     * @throws InvalidQueryException
     */
    public function save($constraint = [], $insertForce = false)
    {
        try {
            if ($this->isUpdate($constraint, $insertForce)) {
                if (empty($constraint)) {
                    $constraint = [$this->primaryKey => $this->getId()];
                }
                $this->beforeSave();
                $columns = $this->prepareColumns();
                if ($columns) {
                    $this->update($columns, $constraint);
                }
                $this->isNew = false;
                $this->afterSave();
                $id = array_values($constraint)[0];
                $key = array_keys($constraint)[0];
                $this->flushRow($id, null, $this->getCacheKey(), $key === $this->primaryKey ? null : $key);
                $this->flushList($this->getCacheKey());
            } elseif ($this->isNew || $insertForce) {
                $this->beforeSave();
                $columns = $this->prepareColumns();
                if ($columns) {
                    $this->insert($columns);
                }
                if (!$this->getId()) {
                    $this->setId($this->getTableGateway($this->tableName)->getLastInsertValue());
                }
                $this->isNew = true;
                $this->afterSave();
                $this->flushList($this->getCacheKey());
            }
        } catch (InvalidQueryException $e) {
            $this->getContainer()->get('log')->logException($e);
            if ($this->transaction) {
                $this->rollback();
            }
            throw $e;
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            throw $e;
        }
        return $this;
    }

    /**
     * Remove data
     *
     * @throws InvalidQueryException
     */
    public function remove()
    {
        if ($this->getId()) {
            try {
                $this->beforeRemove();
                $this->delete([$this->primaryKey => $this->getId()]);
                $this->flushRow($this->getId(), null, $this->getCacheKey());
                $this->flushList($this->getCacheKey());
                $this->storage = [];
                $this->isLoaded = false;
                $this->isNew = true;
                $this->updatedColumns = [];
                $this->afterRemove();
            } catch (InvalidQueryException $e) {
                $this->getContainer()->get('log')->logException($e);
                if ($this->transaction) {
                    $this->rollback();
                }
                throw $e;
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                throw $e;
            }
        }
    }

    /**
     * Get table columns
     *
     * @return array
     */
    public function getColumns()
    {
        if (empty($this->columns)) {
            $cache = $this->getContainer()->get('cache');
            $columns = $cache->fetch($this->tableName, 'TABLE_DESCRIPTION_');
            if (!$columns) {
                $columns = Factory::createSourceFromAdapter($this->getContainer()->get('dbAdapter'))->getColumnNames($this->tableName);
                $cache->save($this->tableName, $columns, 'TABLE_DESCRIPTION_');
            }
            $this->columns[] = $columns;
        }
        return $this->columns;
    }

    /**
     * Get inserting/updating values
     *
     * @return array
     */
    protected function prepareColumns()
    {
        $columns = $this->getColumns();
        $pairs = [];
        foreach ($this->storage as $key => $value) {
            if (in_array($key, $columns) && ($this->isNew || isset($this->updatedColumns[$key]))) {
                $pairs[$key] = $value === '' ? null : $value;
            }
        }
        return $pairs;
    }

    /**
     * Get event dispatcher
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
     * Event before save
     */
    protected function beforeSave()
    {
        $this->getEventDispatcher()->trigger(get_class($this) . '.model.save.before', ['model' => $this]);
    }

    /**
     * Event after save
     */
    protected function afterSave()
    {
        $this->getEventDispatcher()->trigger(get_class($this) . '.model.save.after', ['model' => $this, 'isNew' => $this->isNew]);
        $this->isNew = false;
    }

    /**
     * Event before load data
     *
     * @param \Laminas\Db\Sql\Select $select
     */
    protected function beforeLoad($select)
    {
        $this->getEventDispatcher()->trigger(get_class($this) . '.model.load.before', ['model' => $this]);
    }

    /**
     * Event after load data
     *
     * @param array $result
     */
    protected function afterLoad(&$result)
    {
        $this->isNew = false;
        $this->isLoaded = true;
        $this->updatedColumns = [];
        $toArray = function ($object) {
            if (is_callable([$object, 'toArray'])) {
                return $object->toArray();
            } elseif (is_callable([$object, 'getArrayCopy'])) {
                return $object->getArrayCopy();
            } else {
                return (array) $object;
            }
        };
        if (is_object($result)) {
            $result = $toArray($result);
        }
        if (isset($result[0])) {
            $this->storage = (is_object($result[0]) ? $toArray($result[0]) : $result[0]) + $this->storage;
        } else {
            $this->storage = $result + $this->storage;
        }
        $this->getEventDispatcher()->trigger(get_class($this) . '.model.load.after', ['model' => $this]);
    }

    /**
     * Event before remove
     */
    protected function beforeRemove()
    {
        $this->getEventDispatcher()->trigger(get_class($this) . '.model.remove.before', ['model' => $this]);
    }

    /**
     * Event after remove
     */
    protected function afterRemove()
    {
        $this->getEventDispatcher()->trigger(get_class($this) . '.model.remove.after', ['model' => $this]);
    }

    /**
     * Is the model loaded
     *
     * @return bool
     */
    public function isLoaded()
    {
        return $this->isLoaded;
    }
}
