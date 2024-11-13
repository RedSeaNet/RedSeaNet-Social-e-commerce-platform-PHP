<?php

namespace Redseanet\Lib\Traits;

use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\Sql\Where;
use Laminas\Db\TableGateway\TableGateway;

/**
 * Database handler
 */
trait DB
{
    /**
     * @var array
     */
    protected static $tableGateways = [];
    protected $mainTable = null;

    /**
     * @var ConnectionInterface
     */
    protected $connection = null;

    /**
     * @var bool
     */
    protected $transaction = false;

    /**
     * @param string|TableIdentifier|array $table
     * @return TableGateway
     */
    protected function getTableGateway($table = null)
    {
        if (is_null($table)) {
            $table = is_null($this->mainTable) ? $this->tableName : $this->mainTable;
        } elseif (is_null($this->mainTable)) {
            $this->mainTable = $table;
        }
        if (!isset(static::$tableGateways[$table])) {
            static::$tableGateways[$table] = new TableGateway($table, $this->getContainer()->get('dbAdapter'));
        }
        return static::$tableGateways[$table];
    }

    /**
     * @return \Laminas\Db\Adapter\Driver\ConnectionInterface
     */
    protected function getConnection()
    {
        if (is_null($this->connection)) {
            $this->connection = $this->getContainer()->get('dbAdapter')->getDriver()->getConnection();
        }
        return $this->connection;
    }

    /**
     * @param Where|\Closure|string|array $where
     * @param TableGateway $tableGateway
     * @return ResultSet
     */
    protected function select($where = null, $tableGateway = null)
    {
        $tableGateway = is_null($tableGateway) ? $this->getTableGateway() : $tableGateway;
        if (!is_null($tableGateway)) {
            return $tableGateway->select($where);
        }
        return [];
    }

    /**
     * @param  array $set
     * @param TableGateway $tableGateway
     * @return int
     */
    protected function insert($set, $tableGateway = null)
    {
        $tableGateway = is_null($tableGateway) ? $this->getTableGateway() : $tableGateway;
        if (!is_null($tableGateway)) {
            return $tableGateway->insert($set);
        }
        return 0;
    }

    /**
     * @param  array $set
     * @param  Where|string|array|\Closure $where
     * @param TableGateway $tableGateway
     * @return int
     */
    protected function update($set, $where = null, $tableGateway = null)
    {
        $tableGateway = is_null($tableGateway) ? $this->getTableGateway() : $tableGateway;
        if (!is_null($tableGateway)) {
            return $tableGateway->update($set, $where);
        }
        return 0;
    }

    /**
     * @param array $set
     * @param Where|string|array|\Closure $where
     * @param TableGateway $tableGateway
     * @return int
     */
    protected function upsert($set, $where, $tableGateway = null)
    {
        $select = $this->select($where, $tableGateway)->toArray();
        if (count($select)) {
            return $this->update($set, $where, $tableGateway);
        } else {
            return $this->insert($set + $where, $tableGateway);
        }
    }

    /**
     * @param  Where|\Closure|string|array $where
     * @param TableGateway $tableGateway
     * @return int
     */
    protected function delete($where, $tableGateway = null)
    {
        $tableGateway = is_null($tableGateway) ? $this->getTableGateway() : $tableGateway;
        if (!is_null($tableGateway)) {
            return $tableGateway->delete($where);
        }
        return 0;
    }

    /**
     * @param bool $transaction
     */
    public function setTransaction($transaction)
    {
        $this->transaction = $transaction;
        return $this;
    }

    /**
     * Begin transaction
     */
    protected function beginTransaction()
    {
        if (!$this->getConnection()->inTransaction()) {
            $this->getConnection()->beginTransaction();
            $this->transaction = true;
        }
        return $this;
    }

    /**
     * Commit transaction
     */
    protected function commit()
    {
        if ($this->transaction) {
            $this->getConnection()->commit();
            $this->transaction = false;
        }
        return $this;
    }

    /**
     * Rollback transaction
     */
    protected function rollback()
    {
        if ($this->transaction) {
            $this->getConnection()->rollback();
            $this->transaction = false;
        }
        return $this;
    }
}
