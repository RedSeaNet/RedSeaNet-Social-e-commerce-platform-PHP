<?php

namespace Redseanet\Lib\Indexer\Handler;

use InvalidArgumentException;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\TableGateway\TableGateway;

abstract class AbstractHandler
{
    /**
     * Reindex indexer
     *
     * @throws InvalidArgumentException
     */
    public function reindex()
    {
        $config = $this->getContainer()->get('config');
        if (isset($config['indexer'][$this->entityType])) {
            $provider = new $config['indexer'][$this->entityType]['provider']();
        }
        $adapter = $this->getContainer()->get('dbAdapter');
        $where = is_numeric($this->entityType) ? ['eav_entity_type.id' => $this->entityType] : ['eav_entity_type.code' => $this->entityType];
        if (!isset($provider) || !$provider->provideStructure($this)) {
            $tableGateway = new TableGateway('eav_entity_type', $adapter);
            $select = $tableGateway->getSql()->select();
            $select->join('eav_attribute', 'eav_attribute.type_id=eav_entity_type.id', ['attr' => 'code', 'type', 'is_required', 'default_value', 'is_unique', 'searchable'], 'left')
                    ->where($where)
                    ->columns(['entity_table', 'value_table_prefix', 'entity_type' => 'code']);
            $result = $tableGateway->selectWith($select)->toArray();
            if (count($result) === 0) {
                throw new InvalidArgumentException('Invalid entity type code: ' . $this->entityType);
            }
            if (is_numeric($this->entityType)) {
                $this->entityType = $result[0]['entity_type'];
            }
            $keys = array_keys($tableGateway->selectWith($select->join($result[0]['entity_table'], $result[0]['entity_table'] . '.type_id=eav_entity_type.id', '*', 'left')->limit(1))->toArray()[0]);
            $this->buildStructure($result, $keys);
        }
        if (!isset($provider) || !$provider->provideData($this)) {
            $select->reset('limit')
                    ->join($result[0]['value_table_prefix'] . '_int', 'eav_attribute.id=' . $result[0]['value_table_prefix'] . '_int.attribute_id AND ' . $result[0]['entity_table'] . '.id=' . $result[0]['value_table_prefix'] . '_int.entity_id', ['value_int' => 'value', 'language_int' => 'language_id'], 'left')
                    ->join($result[0]['value_table_prefix'] . '_varchar', 'eav_attribute.id=' . $result[0]['value_table_prefix'] . '_varchar.attribute_id AND ' . $result[0]['entity_table'] . '.id=' . $result[0]['value_table_prefix'] . '_varchar.entity_id', ['value_varchar' => 'value', 'language_varchar' => 'language_id'], 'left')
                    ->join($result[0]['value_table_prefix'] . '_datetime', 'eav_attribute.id=' . $result[0]['value_table_prefix'] . '_datetime.attribute_id AND ' . $result[0]['entity_table'] . '.id=' . $result[0]['value_table_prefix'] . '_datetime.entity_id', ['value_datetime' => 'value', 'language_datetime' => 'language_id'], 'left')
                    ->join($result[0]['value_table_prefix'] . '_text', 'eav_attribute.id=' . $result[0]['value_table_prefix'] . '_text.attribute_id AND ' . $result[0]['entity_table'] . '.id=' . $result[0]['value_table_prefix'] . '_text.entity_id', ['value_text' => 'value', 'language_text' => 'language_id'], 'left')
                    ->join($result[0]['value_table_prefix'] . '_decimal', 'eav_attribute.id=' . $result[0]['value_table_prefix'] . '_decimal.attribute_id AND ' . $result[0]['entity_table'] . '.id=' . $result[0]['value_table_prefix'] . '_decimal.entity_id', ['value_decimal' => 'value', 'language_decimal' => 'language_id'], 'left');
            $entity = new TableGateway($result[0]['entity_table'], $adapter);
            $entitySelect = $entity->getSql()->select();
            $entitySelect->columns(['id'])
                    ->limit(50);
            for ($i = 0; ; $i++) {
                $entitySelect->offset(50 * $i);
                $items = $entity->selectWith($entitySelect)->toArray();
                if (empty($items)) {
                    break;
                }
                $ids = [];
                foreach ($items as $item) {
                    $ids[] = $item['id'];
                }
                $select->reset('where')
                        ->where($where)
                ->where->in($result[0]['entity_table'] . '.id', $ids);
                $data = $tableGateway->selectWith($select)->toArray();
                $items = [];
                foreach ($data as $record) {
                    if (empty($record['id']) && (string) $record['id'] !== '0') {
                        continue;
                    }
                    $languageId = $record['language_varchar'] ?: (
                        $record['language_int'] ?: (
                            $record['language_decimal'] ?: (
                                $record['language_text'] ?:
                            $record['language_datetime']
                            )
                        )
                    );
                    if (!$languageId) {
                        continue;
                    }
                    if (!isset($items[$languageId])) {
                        $items[$languageId] = [];
                    }
                    if (!isset($items[$languageId][$record['id']])) {
                        $items[$languageId][$record['id']] = [];
                        foreach (array_diff($keys, [
                            'type_id', 'attr', 'type',
                            'is_required', 'default_value', 'is_unique', 'code', 'entity_table',
                            'value_table_prefix', 'is_form', 'entity_type', 'searchable'
                        ]) as $key) {
                            $items[$languageId][$record['id']][$key] = $record[$key];
                        }
                    }
                    if ($record['attr']) {
                        $items[$languageId][$record['id']][$record['attr']] = $record['value_varchar'] ?: (
                            $record['value_int'] ?: (
                                $record['value_decimal'] ?: (
                                    $record['value_text'] ?:
                                $record['value_datetime']
                                )
                            )
                        );
                    }
                }
                $languages = new Language();
                $languages->columns(['id'])->order('id ASC');
                $languageIds = $languages->load(false)->toArray();
                foreach ($languageIds as $key => $language) {
                    if (!isset($items[$language['id']])) {
                        for ($i = $key - 1; $i >= 0 && isset($items[$languageIds[$i]['id']]); $i--) {
                            $items[$language['id']] = $items[$languageIds[$i]['id']];
                        }
                    }
                }
                $this->buildData($items);
            }
            $this->createIndexes($result, $keys);
        }
    }

    /**
     * Build database structure
     *
     * @param array $attributes
     * @param array $columns
     * @param callable $extra
     */
    abstract public function buildStructure($attributes, $columns, $extra = null);

    /**
     * Append data to database
     *
     * @param array $data
     */
    abstract public function buildData($data);

    /**
     * Create indexes
     * @param array $columns
     */
    abstract public function createIndexes($columns, $keys = null);

    /**
     * Select data from indexer
     *
     * @param int $languageId
     * @param array|\Laminas\Db\Sql\Select $constraint
     * @param array $options
     * @throws \Redseanet\Lib\Exception\BadIndexerException
     */
    abstract public function select($languageId, $constraint = [], array $options = []);

    /**
     * Insert data into indexer
     *
     * @param int $languageId
     * @param array $values
     * @param array $options
     * @throws \Redseanet\Lib\Exception\BadIndexerException
     */
    abstract public function insert($languageId, $values, array $options = []);

    /**
     * Update data of indexer
     *
     * @param int $languageId
     * @param array $values
     * @param array $constraint
     * @param array $options
     * @throws \Redseanet\Lib\Exception\BadIndexerException
     */
    abstract public function update($languageId, $values, $constraint = [], array $options = []);

    /**
     * Insert/Update data of indexer
     *
     * @param int $languageId
     * @param array $values
     * @param array $constraint
     * @param array $options
     * @throws \Redseanet\Lib\Exception\BadIndexerException
     */
    abstract public function upsert($languageId, $values, $constraint = [], array $options = []);

    /**
     * Delete data of indexer
     *
     * @param int $languageId
     * @param array $constraint
     * @param array $options
     * @throws \Redseanet\Lib\Exception\BadIndexerException
     */
    abstract public function delete($languageId, $constraint = [], array $options = []);
}
