<?php

namespace Redseanet\Lib\Indexer\Handler;

use Exception;
use Redseanet\Lib\Exception\BadIndexerException;
use Redseanet\Lib\Model\Collection\Language;
use MongoDB\Driver\Manager;
use MongoDB\Collection as MongoDBCollection;
use Laminas\Db\Sql\Select;

/**
 * MongoDB indexer handler
 */
class MongoDB extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Container;

    /**
     * @var array
     */
    protected $collection = [];

    /**
     * @var Manager
     */
    protected $manager = null;

    /**
     * @var string
     */
    protected $db = null;

    /**
     * @var string
     */
    protected $entityType = null;

    public function __construct(Manager $manager, $db, $entityType)
    {
        $this->manager = $manager;
        $this->db = $db;
        $this->entityType = $entityType;
    }

    /**
     * Get collection based on language id
     *
     * @param int $languageId
     * @return MongoDBCollection
     */
    protected function getCollection($languageId)
    {
        if (!isset($this->collection[$languageId])) {
            $this->collection[$languageId] = new MongoDBCollection($this->manager, $this->db, $this->entityType . '_' . $languageId . '_index');
        }
        return $this->collection[$languageId];
    }

    /**
     * {@inhertdoc}
     */
    public function delete($languageId, $constraint = [], array $options = [])
    {
        try {
            return $this->getCollection($languageId)->deleteMany($constraint, $options);
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }

    /**
     * {@inhertdoc}
     */
    public function insert($languageId, $values, array $options = [])
    {
        try {
            $values['_id'] = $values['id'];
            return $this->getCollection($languageId)->insertOne($values, $options);
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }

    /**
     * Generate option based on SQL
     *
     * @param Select $select
     * @return array
     */
    protected function getOptionFromSelect(Select $select)
    {
        $options = [];
        $states = $select->getRawState();
        if ($limit = $states['limit']) {
            $options['limit'] = (int) $limit;
        }
        if ($skip = $states['offset']) {
            $options['skip'] = (int) $skip;
        }
        if ($sort = $states['order']) {
            $options['sort'] = [];
            foreach ($sort as $key => $value) {
                $parts = is_int($key) ? explode(' ', trim($value)) : [$key, $value];
                $options['sort'][$parts[0]] = !isset($parts[1]) || strcasecmp($parts[1], 'desc') ? 1 : -1;
            }
        }
        return $options;
    }

    /**
     * Generate filter based on SQL
     *
     * @param Select $select
     * @return array
     */
    protected function getFilterFromSelect(Select $select)
    {
        $predicates = $select->getRawState('where')->getExpressionData();
        $parts = [];
        $hasOr = false;
        for ($i = 0; $i < count($predicates); $i++) {
            $predicate = $predicates[$i];
            if (is_array($predicate)) {
                $expression = preg_replace('#^(?:\s*\%s\s+)([^\s]+).+$#', '$1', $predicate[0]);
                $value = $predicate[1];
                if ($expression === 'LIKE') {
                    $v = str_replace('%', '.+', $predicate[1][1]);
                    if (substr($predicate[1][1], 0, 1) !== '%') {
                        $v = '^' . $v;
                    }
                    if (substr($predicate[1][1], -1) !== '%') {
                        $v .= '$';
                    }
                    $parts[$i] = [array_shift($value) => [
                        '$regex' => $v
                    ]];
                } else {
                    $parts[$i] = [array_shift($value) => [
                        '$' . strtolower(str_replace(['>=', '<=', '<>', '!=', '>', '<', '='], ['gte', 'lte', 'ne', 'ne', 'gt', 'lt', 'eq'], $expression)) => count($value) > 1 ? array_values($value) : ($expression === 'IN' ? (array) $predicate[1][1] : $predicate[1][1])
                    ]];
                }
            }
        }
        $handleAnd = function ($a, $b) {
            if (isset($a['$and'])) {
                $a = $a['$and'];
            } else {
                $a = [$a];
            }
            if (isset($b['$and'])) {
                $b = $b['$and'];
            } else {
                $b = [$b];
            }
            return array_merge($a, $b);
        };
        for ($i = 0; $i < count($predicates) - 1; $i++) {
            $predicate = $predicates[$i];
            if (is_string($predicate)) {
                if (trim($predicate) === 'OR') {
                    $hasOr = true;
                } else {
                    for ($j = $i - 1; $j > 0; $j--) {
                        if (isset($parts[$j])) {
                            break;
                        }
                    }
                    for ($k = $i + 1; $k < count($predicates); $k++) {
                        if (isset($parts[$k])) {
                            break;
                        }
                    }
                    $parts[$k] = ['$and' => $handleAnd($parts[$j], $parts[$k])];
                    unset($parts[$j]);
                }
            }
        }
        if ($hasOr) {
            $parts = ['$or' => array_values($parts)];
        } elseif (!empty($parts)) {
            $parts = array_values($parts)[0];
        }
        return $parts;
    }

    /**
     * {@inhertdoc}
     */
    public function select($languageId, $constraint = [], array $options = [])
    {
        try {
            if (isset($options['offset'])) {
                $options['skip'] = $options['offset'];
                unset($options['offset']);
            }
            if ($constraint instanceof Select) {
                $options += $this->getOptionFromSelect($constraint);
                $constraint = $this->getFilterFromSelect($constraint);
            }
            return $this->getCollection($languageId)->find($constraint, $options)->toArray();
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }

    /**
     * {@inhertdoc}
     */
    public function update($languageId, $values, $constraint = [], array $options = [])
    {
        try {
            return $this->getCollection($languageId)->updateOne($constraint, ['$set' => $values], $options);
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }

    /**
     * {@inhertdoc}
     */
    public function upsert($languageId, $values, $constraint = [], array $options = [])
    {
        try {
            return $this->getCollection($languageId)->updateOne($constraint, ['$set' => $values], ['upsert' => true] + $options);
        } catch (Exception $e) {
            throw new BadIndexerException($e->getMessage());
        }
    }

    /**
     * {@inhertdoc}
     */
    public function buildData($data)
    {
        foreach ($data as $languageId => $values) {
            $sets = [];
            foreach ($values as $id => $set) {
                $set['_id'] = (string) $id;
                $sets[] = $set;
            }
            $this->getCollection($languageId)->insertMany($sets);
        }
    }

    /**
     * {@inhertdoc}
     */
    public function buildStructure($columns, $keys = null, $extra = null)
    {
        $languages = new Language();
        foreach ($languages as $language) {
            $this->getCollection($language['id'])->drop();
        }
    }

    /**
     * {@inhertdoc}
     */
    public function createIndexes($columns, $keys = null)
    {
        $languages = new Language();
        foreach ($languages as $language) {
            $indexes = [
                ['key' => ['id' => 1]]
            ];
            if (!is_null($keys)) {
                $indexes[] = ['key' => ['store_id' => 1]];
                $indexes[] = ['key' => ['increment_id' => 1]];
            }
            foreach ($columns as $column) {
                $index = [];
                if (!empty($column['is_unique'])) {
                    $index['key'] = [$column['attr'] => 1]; //, 'unique' => true];
                }
                if (isset($column['fulltext'])) {
                    $index['key'] = [$column['attr'] => 'text'];
                }
                if (!empty($index)) {
                    $indexes[] = $index;
                }
            }
            $this->getCollection($language['id'])->createIndexes($indexes);
        }
    }
}
