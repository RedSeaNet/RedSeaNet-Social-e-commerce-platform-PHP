<?php

namespace Redseanet\Lib\Indexer\Handler;

use Exception;
use Redseanet\Lib\Exception\BadIndexerException;
use Redseanet\Lib\Db\Sql\Ddl\Column\Timestamp;
use Redseanet\Lib\Db\Sql\Ddl\Column\UnsignedInteger;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Metadata\Source\Factory;
use Laminas\Db\Sql\Ddl;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\TableGateway;

/**
 * Database indexer handler
 */
class Database extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * @var string
     */
    protected $entityType = null;

    /**
     * @var array
     */
    protected $tableGateways = [];

    public function __construct($entityType)
    {
        $this->entityType = $entityType;
    }

    /**
     * {@inhertdoc}
     */
    public function reindex()
    {
        $config = $this->getContainer()->get('config');
        if (isset($config['indexer'][$this->entityType])) {
            $provider = new $config['indexer'][$this->entityType]['provider']();
        }
        $adapter = $this->getContainer()->get('dbAdapter');
        if (!isset($provider)) {
            try {
                if (is_numeric($this->entityType)) {
                    $tableGateway = new TableGateway('eav_entity_type');
                    $result = $tableGateway->select(['id' => $this->entityType])->toArray();
                    if (count($result) === 0) {
                        throw new InvalidArgumentException('Invalid entity type code: ' . $this->entityType);
                    }
                    $this->entityType = $result[0]['code'];
                }
                $adapter->query('call reindex(\'' . $this->entityType . '\');', $adapter::QUERY_MODE_EXECUTE);
            } catch (Exception $e) {
                $tableGateway = new TableGateway('eav_attribute', $adapter);
                $select = $tableGateway->getSql()->select();
                $select->columns(['id', 'attr' => 'code', 'type', 'default_value', 'is_unique', 'searchable'])
                        ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', ['entity_type' => 'code', 'entity_table', 'value_table_prefix'], 'left')
                        ->where(is_numeric($this->entityType) ? ['eav_entity_type.id' => $this->entityType] : ['eav_entity_type.code' => $this->entityType]);
                $result = $tableGateway->selectWith($select)->toArray();
                if (count($result) === 0) {
                    throw new InvalidArgumentException('Invalid entity type code: ' . $this->entityType);
                }
                if (is_numeric($this->entityType)) {
                    $this->entityType = $result[0]['entity_type'];
                }
                $metadata = Factory::createSourceFromAdapter($adapter);
                $keys = array_combine((array) $metadata->getColumnNames($result[0]['entity_table']), (array) $metadata->getColumns($result[0]['entity_table']));
                unset($keys['id'], $keys['store_id'], $keys['status'], $keys['created_at'], $keys['updated_at'], $keys['type_id'], $keys['attribute_set_id']);
                $languages = new Language();
                foreach ($languages as $language) {
                    $table = $this->entityType . '_' . $language['id'] . '_index';
                    $adapter->query('DROP TABLE IF EXISTS ' . $table, $adapter::QUERY_MODE_EXECUTE);
                    $create = 'CREATE TABLE ' . $table . '(id INT UNSIGNED NOT NULL,store_id INT UNSIGNED NOT NULL,attribute_set_id INT UNSIGNED NOT NULL,status BOOLEAN DEFAULT 1,created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,';
                    $insert = 'INSERT INTO ' . $table . '(';
                    $columns = 'id,store_id,attribute_set_id,status,created_at,updated_at';
                    $attrs = '';
                    $select = '';
                    foreach ($keys as $key => $data) {
                        $create .= $key . ' ' . $data->getDataType();
                        if ($data->getDataType() === 'varchar') {
                            $create .= '(255),';
                        } elseif ($data->getDataType() === 'decimal') {
                            $create .= '(12,4),';
                        } else {
                            $create .= ',';
                        }
                        $columns .= ',' . $key;
                    }
                    foreach ($result as $attr) {
                        $attrs .= ',' . $attr['attr'];
                        $create .= $attr['attr'] . ' ' . $attr['type'];
                        if ($attr['type'] === 'varchar') {
                            $create .= '(255)';
                        } elseif ($attr['type'] === 'decimal') {
                            $create .= '(12,4)';
                        }
                        if (!is_null($attr['default_value']) && $attr['default_value'] !== '' && $attr['type'] !== 'text') {
                            if ($attr['type'] === 'varchar' || $attr['type'] === 'datetime' && $attr['default_value'] !== 'CURRENT_TIMESTAMP') {
                                $create .= ' DEFAULT \'' . $attr['default_value'] . '\',';
                            } else {
                                $create .= ' DEFAULT ' . $attr['default_value'] . ',';
                            }
                        } else {
                            $create .= ',';
                        }
                        $select .= ' LEFT JOIN (SELECT value AS ' . $attr['attr'] .
                                ',entity_id FROM ' . $result[0]['value_table_prefix'] .
                                '_' . $attr['type'] . ' WHERE language_id=' . $language['id'] .
                                ' AND attribute_id=' . $attr['id'] . ') as v' .
                                $attr['id'] . ' ON e.id=v' . $attr['id'] . '.entity_id';
                    }
                    $insert .= $columns . $attrs . ') SELECT ' . $columns . $attrs .
                            ' FROM (SELECT ' . $columns . ' FROM ' . $result[0]['entity_table'] . ') AS e' . $select;
                    $adapter->query($create . 'PRIMARY KEY(id));', $adapter::QUERY_MODE_EXECUTE);
                    $adapter->query($insert, $adapter::QUERY_MODE_EXECUTE);
                }
                $this->createIndexes($result, $keys);
            }
        } else {
            parent::reindex();
        }
    }

    /**
     * {@inhertdoc}
     */
    public function buildStructure($columns, $keys = null, $extra = null)
    {
        $adapter = $this->getContainer()->get('dbAdapter');
        $platform = $adapter->getPlatform();
        $languages = new Language();
        $entityTable = $columns[0]['entity_table'];
        foreach ($languages as $language) {
            $table = $this->entityType . '_' . $language['id'] . '_index';
            $adapter->query(
                'DROP TABLE IF EXISTS ' . $table,
                $adapter::QUERY_MODE_EXECUTE
            );
            $ddl = new Ddl\CreateTable($table);
            $ddl->addColumn(new UnsignedInteger('id', false, 0))
                    ->addColumn(new UnsignedInteger('store_id', false, 0))
                    ->addConstraint(new Ddl\Constraint\PrimaryKey('id'));
            if (!is_null($keys)) {
                $ddl->addColumn(new UnsignedInteger('attribute_set_id', false, 0))
                        ->addColumn(new Ddl\Column\Boolean('status', true, 1))
                        ->addColumn(new Timestamp('created_at', true, 'CURRENT_TIMESTAMP'))
                        ->addColumn(new Timestamp('updated_at', true, null, ['on_update' => 'CURRENT_TIMESTAMP']));
                foreach (array_diff($keys, [
                    'id', 'store_id', 'status', 'created_at',
                    'updated_at', 'type_id', 'attribute_set_id', 'attr', 'type',
                    'is_required', 'default_value', 'is_unique', 'code', 'entity_table',
                    'value_table_prefix', 'is_form', 'entity_type'
                ]) as $key) {
                    $ddl->addColumn(new Ddl\Column\Varchar($key, 255, true, ''));
                }
            }
            foreach ($columns as $attr) {
                if ($attr['attr']) {
                    if ($attr['type'] === 'int') {
                        $column = new Ddl\Column\Integer($attr['attr'], true, is_null($attr['default_value']) ? null : (int) $attr['default_value']);
                    } elseif ($attr['type'] === 'varchar') {
                        $column = new Ddl\Column\Varchar($attr['attr'], 255, true, $attr['default_value']);
                    } elseif ($attr['type'] === 'datetime') {
                        $column = new Ddl\Column\Datetime($attr['attr'], true, $attr['default_value'] ? date('Y-m-d H:i:s', strtotime($attr['default_value'])) : null);
                    } elseif ($attr['type'] === 'decimal') {
                        $column = new Ddl\Column\Decimal($attr['attr'], 12, 4, true, (float) $attr['default_value']);
                    } else {
                        $column = new Ddl\Column\Text($attr['attr'], 65535, true);
                    }
                    $ddl->addColumn($column);
                }
            }
            if (is_callable($extra)) {
                $extra($ddl);
            }
            $adapter->query(
                $ddl->getSqlString($platform),
                $adapter::QUERY_MODE_EXECUTE
            );
        }
    }

    /**
     * {@inhertdoc}
     */
    public function buildData($data)
    {
        $adapter = $this->getContainer()->get('dbAdapter');
        $platform = $adapter->getPlatform();
        $isOracle = $platform->getName() === 'Oracle';
        try {
            foreach ($data as $languageId => $values) {
                $table = $this->entityType . '_' . $languageId . '_index';
                $columns = [];
                foreach ($values as $sets) {
                    if (count($columns) < count($sets)) {
                        $columns = array_keys($sets);
                    }
                }
                $sql = 'INSERT INTO ' . $platform->quoteIdentifierInFragment($table) . '(';
                foreach ($columns as $key) {
                    $sql .= $platform->quoteIdentifierInFragment($key) . ',';
                }
                $sql = rtrim($sql, ',') . ') VALUES ';
                if ($isOracle) {
                    $sql .= '(' . str_repeat('?,', count($columns));
                    $sql = substr_replace($sql, '),', -1);
                }
                $params = [];
                foreach ($values as $sets) {
                    if ($isOracle) {
                        $params = [];
                    }
                    foreach ($columns as $key) {
                        $params[] = $sets[$key] ?? null;
                    }
                    if ($isOracle) {
                        $adapter->query(rtrim($sql, ','), $params);
                    } else {
                        $sql .= '(' . str_repeat('?,', count($columns));
                        $sql = substr_replace($sql, '),', -1);
                    }
                }
                if (!$isOracle) {
                    $adapter->query(rtrim($sql, ','), $params);
                }
            }
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
        }
    }

    /**
     * {@inhertdoc}
     */
    public function createIndexes($columns, $keys = null)
    {
        $adapter = $this->getContainer()->get('dbAdapter');
        $languages = new Language();
        $entityTable = $columns[0]['entity_table'];
        foreach ($languages as $language) {
            $table = $this->entityType . '_' . $language['id'] . '_index';
            foreach ($columns as $attr) {
                if ($attr['attr'] && ($attr['is_unique'] || $attr['searchable']) && $attr['type'] !== 'text') {
                    $adapter->query('CREATE INDEX IDX_' . strtoupper($table) . '_' . strtoupper($attr['attr']) . ' ON ' . $table . '(' . $attr['attr'] . ');', $adapter::QUERY_MODE_EXECUTE);
                }
            }
            $adapter->query('CREATE INDEX IDX_' . strtoupper($table) . '_STORE_ID ON ' . $table . '(store_id);', $adapter::QUERY_MODE_EXECUTE);
            $adapter->query('ALTER TABLE ' . $table . ' ADD CONSTRAINT FK_' . strtoupper($table) .
                    '_ID_' . strtoupper($entityTable) . '_ID FOREIGN KEY (id) REFERENCES ' . $entityTable . '(id) ON DELETE CASCADE ON UPDATE CASCADE;', $adapter::QUERY_MODE_EXECUTE);
            $adapter->query('ALTER TABLE ' . $table . ' ADD CONSTRAINT FK_' . strtoupper($table) .
                    '_STORE_ID_CORE_STORE_ID FOREIGN KEY (store_id) REFERENCES core_store(id) ON DELETE CASCADE ON UPDATE CASCADE;', $adapter::QUERY_MODE_EXECUTE);
            if ($keys) {
                $adapter->query('CREATE INDEX IDX_' . strtoupper($table) . '_ATTR_SET_ID' . ' ON ' . $table . '(attribute_set_id);', $adapter::QUERY_MODE_EXECUTE);
                $adapter->query('ALTER TABLE ' . $table . ' ADD CONSTRAINT FK_' . strtoupper($table) . '_ATTR_SET_ID_EAV_ATTR_SET_ID FOREIGN KEY (attribute_set_id) REFERENCES eav_attribute_set(id) ON DELETE CASCADE ON UPDATE CASCADE;', $adapter::QUERY_MODE_EXECUTE);
            }
        }
    }

    /**
     * Get table gateway based on language id
     *
     * @param int $languageId
     * @return TableGateway
     */
    protected function getTableGateway($languageId)
    {
        if (is_array($languageId) || is_object($languageId)) {
            $languageId = $languageId['id'];
        }
        if (!isset($this->tableGateways[$languageId])) {
            $this->tableGateways[$languageId] = new TableGateway($this->entityType . '_' . $languageId . '_index', $this->getContainer()->get('dbAdapter'));
        }
        return $this->tableGateways[$languageId];
    }

    /**
     * {@inhertdoc}
     */
    public function select($languageId, $where = [], array $options = [])
    {
        try {
            if (!$where instanceof Select) {
                $where = $this->getTableGateway($languageId)->getSql()->select()->where($where);
                if (!empty($options['limit'])) {
                    $where->limit($options['limit']);
                }
                if (!empty($options['offset'])) {
                    $where->offset($options['offset']);
                }
            }
            return $this->getTableGateway($languageId)->selectWith($where)->toArray();
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }

    /**
     * {@inhertdoc}
     */
    public function insert($languageId, $set, array $options = [])
    {
        try {
            return $this->getTableGateway($languageId)->insert($set);
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }

    /**
     * {@inhertdoc}
     */
    public function update($languageId, $set, $where = [], array $options = [])
    {
        try {
            if (empty($set)) {
                return false;
            }
            return $this->getTableGateway($languageId)->update($set, $where);
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }

    /**
     * {@inhertdoc}
     */
    public function upsert($languageId, $set, $where = [], array $options = [])
    {
        $select = $this->select($languageId, $where)->toArray();
        if (count($select)) {
            return $this->update($languageId, $set, $where);
        } else {
            return $this->insert($languageId, $set + $where);
        }
    }

    /**
     * {@inhertdoc}
     */
    public function delete($languageId, $where = [], array $options = [])
    {
        try {
            return $this->getTableGateway($languageId)->delete($where);
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }
}
