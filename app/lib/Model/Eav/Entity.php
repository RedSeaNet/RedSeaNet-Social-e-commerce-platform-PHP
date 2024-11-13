<?php

namespace Redseanet\Lib\Model\Eav;

use Exception;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Exception\BadIndexerException;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Collection\Eav\Attribute as AttributeCollection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Sql\Predicate\In;

abstract class Entity extends AbstractModel
{
    public const ENTITY_TYPE = '';

    protected $entityTable = '';
    protected $valueTablePrefix = '';
    public $languageId = 0;
    protected $attributes = [];
    protected $outerTransaction = true;

    public function __construct($languageId = 0, $input = [])
    {
        $this->setData($input);
        if ($languageId) {
            $this->languageId = $languageId;
        } else {
            $this->languageId = Bootstrap::getLanguage()->getId();
        }
        $this->construct();
    }

    public function setLanguageId($languageId)
    {
        $languages = new Language();
        $languages->columns(['id']);
        $tmpLanguageId = [];
        foreach ($languages as $language) {
            $tmpLanguageId[] = $language['id'];
        }
        if (in_array($languageId, $tmpLanguageId)) {
            $this->languageId = $languageId;
        }
    }

    protected function init($primaryKey = 'id', $columns = ['id', 'type_id', 'attribute_set_id', 'store_id', 'increment_id', 'status'], $null = null)
    {
        $this->cacheKey = static::ENTITY_TYPE;
        $this->columns = $columns;
        $this->primaryKey = $primaryKey;
    }

    public function load($id, $key = null)
    {
        if (!$this->isLoaded) {
            try {
                $this->beforeLoad(null);
                if ($result = $this->loadFromCache($id, $key)) {
                    $this->afterLoad($result);
                } elseif ($result = $this->loadFromIndexer($id, $key)) {
                    $this->afterLoad($result);
                    $this->flushRow($this->languageId . '-' . $this->storage[$this->primaryKey], $this->storage, $this->getCacheKey(), $this->catchLifeTime);
                    if ($key !== $this->primaryKey) {
                        $this->addCacheAlias($key . '=' . $id, $this->storage[$this->primaryKey], $this->getCacheKey(), $this->catchLifeTime);
                    }
                }
            } catch (BadIndexerException $e) {
                if ($result = $this->loadFromDb($id, $key)) {
                    $this->afterLoad($result);
                    $this->flushRow($this->languageId . '-' . $this->storage[$this->primaryKey], $this->storage, $this->getCacheKey(), $this->catchLifeTime);
                    if ($key !== $this->primaryKey) {
                        $this->addCacheAlias($key . '=' . $id, $this->storage[$this->primaryKey], $this->getCacheKey(), $this->catchLifeTime);
                    }
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

    protected function loadFromCache($id, $key = null)
    {
        if (is_null($key) || $key === $this->primaryKey) {
            $key = $this->primaryKey;
            $result = $this->fetchRow($this->languageId . '-' . $id, null, $this->getCacheKey());
        } else {
            $result = $this->fetchRow($this->languageId . '-' . $id, $this->languageId . '-' . $key, $this->getCacheKey());
        }
        return $result;
    }

    protected function loadFromIndexer($id, $key = null)
    {
        return $this->getContainer()->get('indexer')->select(static::ENTITY_TYPE, $this->languageId, [(is_null($key) ? $this->primaryKey : $key) => (string) $id], ['noCache' => 1]);
    }

    protected function getEntityTable()
    {
        if (!$this->entityTable) {
            $tableGateway = $this->getTableGateway('eav_entity_type');
            $select = $tableGateway->getSql()->select();
            $select->where(['eav_entity_type.code' => static::ENTITY_TYPE]);
            $result = $tableGateway->selectWith($select)->toArray();
            if (count($result)) {
                $this->entityTable = $result[0]['entity_table'];
                $this->valueTablePrefix = $result[0]['value_table_prefix'];
                $this->getTableGateway($this->entityTable);
            }
        }
        return $this->entityTable;
    }

    protected function loadFromDb($id, $key = null)
    {
        $select = $this->getTableGateway($this->getEntityTable())->getSql()->select();
        $select->join('eav_attribute', 'eav_attribute.type_id=' . $this->entityTable . '.type_id', ['attr' => 'code', 'type', 'is_required', 'default_value', 'is_unique'], 'left')
                ->join($this->valueTablePrefix . '_int', 'eav_attribute.id=' . $this->valueTablePrefix . '_int.attribute_id', ['value_int' => 'value'], 'left')
                ->join($this->valueTablePrefix . '_varchar', 'eav_attribute.id=' . $this->valueTablePrefix . '_varchar.attribute_id', ['value_varchar' => 'value'], 'left')
                ->join($this->valueTablePrefix . '_datetime', 'eav_attribute.id=' . $this->valueTablePrefix . '_datetime.attribute_id', ['value_datetime' => 'value'], 'left')
                ->join($this->valueTablePrefix . '_text', 'eav_attribute.id=' . $this->valueTablePrefix . '_text.attribute_id', ['value_text' => 'value'], 'left')
                ->join($this->valueTablePrefix . '_decimal', 'eav_attribute.id=' . $this->valueTablePrefix . '_decimal.attribute_id', ['value_decimal' => 'value'], 'left');
        $items = [];
        try {
            $records = $this->getTableGateway()->selectWith($select)->toArray();
        } catch (InvalidQueryException $e) {
            $this->getContainer()->get('log')->logException($e);
            return [];
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            return [];
        }
        foreach ($records as $record) {
            if (!isset($record['id']) || !$record['id']) {
                continue;
            }
            if (!isset($items[$record['id']])) {
                $items[$record['id']] = [];
                foreach (array_diff(array_keys($record), [
                    'type_id', 'attr', 'type', 'is_required', 'default_value',
                    'is_unique', 'code', 'entity_table', 'value_table_prefix',
                    'is_form', 'value_varchar', 'value_decimal', 'value_text',
                    'value_int', 'value_datetime',
                    'language_varchar', 'language_decimal', 'language_text',
                    'language_int', 'language_datetime'
                ]) as $key) {
                    $items[$record['id']][$key] = $record[$key];
                }
            }
            if ($record['attr']) {
                $items[$record['id']][$record['attr']] = $record['value_int'] ?: (
                    $record['value_varchar'] ?: (
                        $record['value_decimal'] ?: (
                            $record['value_text'] ?:
                            $record['value_datetime']
                        )
                    )
                );
            }
        }
        if (is_null($key) || $key === $this->primaryKey) {
            return $items[$id] ?? [];
        } else {
            foreach ($items as $item) {
                if (isset($item[$key]) && $item[$key] == $id) {
                    return $item;
                }
            }
        }
        return [];
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        return !$insertForce && $this->getId();
    }

    public function save($constraint = [], $insertForce = false, $multilingual = [])
    {
        $isUpdate = $this->isUpdate($constraint, $insertForce);
        try {
            if ($isUpdate || $this->isNew) {
                if (!$this->transaction) {
                    $this->beginTransaction();
                    $this->outerTransaction = false;
                }
                $this->beforeSave();
                $columns = $this->prepareColumns();
                $attributes = $this->prepareAttributes();
                $tableGateway = $this->getTableGateway($this->getEntityTable());
                if ($isUpdate) {
                    $this->isNew = false;
                    if ($columns) {
                        $tableGateway->update($columns, ['id' => $this->getId()]);
                    }
                } else {
                    $this->isNew = true;
                    $tableGateway->insert($columns);
                    $this->setId($tableGateway->getLastInsertValue());
                }
                $columns = array_diff_key($columns, ['type_id' => 1]);
                $adapter = $this->getContainer()->get('dbAdapter');
                $tableGateways = [];
                $languages = new Language();
                $languages->columns(['id']);
                $index = [];
                foreach ($this->attributes as $attr) {
                    $attribute = $attributes[$attr['code']] ?? null;
                    if (!isset($tableGateways[$attr['type']])) {
                        $tableGateways[$attr['type']] = $this->getTableGateway($this->valueTablePrefix . '_' . $attr['type']);
                    }
                    if (is_array($attribute)) {
                        foreach ($attribute as $id => $value) {
                            if (!isset($index[$id])) {
                                $index[$id] = [];
                            }
                            $index[$id][$attr['code']] = $value;
                            if ($isUpdate) {
                                $this->upsert(['value' => $value], ['language_id' => $id, 'entity_id' => $this->getId(), 'attribute_id' => $attr['id']], $tableGateways[$attr['type']]);
                            } else {
                                $this->insert(['value' => $value, 'language_id' => $id, 'entity_id' => $this->getId(), 'attribute_id' => $attr['id']], $tableGateways[$attr['type']]);
                            }
                        }
                    } else {
                        if ($isUpdate) {
                            if (!isset($index[$this->languageId])) {
                                $index[$this->languageId] = [];
                            }
                            if (is_null($attribute)) {
                                $index[$this->languageId][$attr['code']] = null;
                                $this->delete(['language_id' => $this->languageId, 'entity_id' => $this->getId(), 'attribute_id' => $attr['id']], $tableGateways[$attr['type']]);
                            } else {
                                $index[$this->languageId][$attr['code']] = $attribute;
                                if ($attr['multilingual'] == '0') {
                                    foreach ($languages as $language) {
                                        $this->upsert(['value' => $attribute], ['language_id' => $language['id'], 'entity_id' => $this->getId(), 'attribute_id' => $attr['id']], $tableGateways[$attr['type']]);
                                        $index[$language['id']][$attr['code']] = $attribute;
                                        $multilingual[$language['id']][$attr['code']] = $attribute;
                                    }
                                } else {
                                    $this->upsert(['value' => $attribute], ['language_id' => $this->languageId, 'entity_id' => $this->getId(), 'attribute_id' => $attr['id']], $tableGateways[$attr['type']]);
                                }
                            }
                        } else {
                            foreach ($languages as $language) {
                                if (!isset($index[$language['id']])) {
                                    $index[$language['id']] = [];
                                }
                                $index[$language['id']][$attr['code']] = $attribute;
                                if (!is_null($attribute)) {
                                    $this->insert(['value' => $attribute, 'language_id' => $language['id'], 'entity_id' => $this->getId(), 'attribute_id' => $attr['id']], $tableGateways[$attr['type']]);
                                }
                            }
                        }
                    }
                }
                if ($isUpdate) {
                    $this->getContainer()->get('indexer')->update(static::ENTITY_TYPE, $this->languageId, (isset($index[$this->languageId]) ? $columns + $index[$this->languageId] : $columns), [$this->primaryKey => $this->getId()]);
                    if (count($multilingual) > 0) {
                        unset($multilingual[$this->languageId]);
                        foreach ($multilingual as $key => $cols) {
                            $this->getContainer()->get('indexer')->update(static::ENTITY_TYPE, $key, $cols, [$this->primaryKey => $this->getId()]);
                        }
                    }
                } else {
                    foreach ($languages as $language) {
                        $this->getContainer()->get('indexer')->insert(static::ENTITY_TYPE, $language['id'], [$this->primaryKey => $this->getId()] + $columns + ($index[$language['id']] ?? []));
                    }
                }
                $this->afterSave();
                if (!$this->outerTransaction) {
                    $this->commit();
                }
                if ($isUpdate) {
                    $this->flushRow($this->languageId . '-' . $this->getId(), null, $this->getCacheKey());
                }
                $this->flushList($this->getCacheKey());
            }
        } catch (InvalidQueryException $e) {
            $this->getContainer()->get('log')->logException($e);
            $this->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            throw $e;
        }
        return $this;
    }

    public function remove()
    {
        if ($this->getId()) {
            try {
                $this->beforeRemove();
                $this->getTableGateway($this->getEntityTable())->delete([$this->primaryKey => $this->getId()]);
                $languages = new Language();
                $languages->columns(['id']);
                foreach ($languages as $language) {
                    $this->getContainer()->get('indexer')->delete(static::ENTITY_TYPE, $language['id'], [$this->primaryKey => $this->getId()]);
                }
                $this->flushRow($this->languageId . '-' . $this->getId(), null, $this->getCacheKey());
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

    protected function prepareAttributes()
    {
        if (empty($this->attributes)) {
            $this->attributes = new AttributeCollection();
            $this->attributes->withSet()->columns(['code', 'id', 'type', 'multilingual'])
                    ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id', [], 'left')
                    ->where(['eav_entity_type.code' => static::ENTITY_TYPE, 'attribute_set_id' => $this->storage['attribute_set_id']]);
            $this->attributes->load(true, true);
        }
        $attrs = [];
        $this->attributes->walk(function ($attr) use (&$attrs, &$datetime) {
            $attrs[$attr['code']] = $attr['type'];
        });
        $pairs = [];
        foreach ($this->storage as $key => $value) {
            if (isset($attrs[$key])) {
                if ($attrs[$key] === 'datetime') {
                    $timestamp = !empty($value) ? strtotime($value) : '';
                    if ($timestamp) {
                        $pairs[$key] = date('Y-m-d H:i:s', $timestamp);
                    } else {
                        $pairs[$key] = null;
                    }
                } elseif ($attrs[$key] === 'varchar' || $attrs[$key] === 'text') {
                    $pairs[$key] = is_array($value) ? json_encode($value) : $value;
                } else {
                    $pairs[$key] = $value === '' ? null :
                            ($attrs[$key] === 'decimal' ? (float) $value : (int) $value);
                }
            }
        }
        return $pairs;
    }

    protected function beforeSave()
    {
        $attributes = new AttributeCollection();
        $attributes->withSet()->columns(['code'])->where(['eav_attribute_set.id' => $this->storage['attribute_set_id']])->where(new In('input', ['multiselect', 'checkbox']));
        foreach ($attributes as $attribute) {
            if (!empty($this->storage[$attribute['code']]) && is_array($this->storage[$attribute['code']])) {
                $this->storage[$attribute['code']] = implode(',', $this->storage[$attribute['code']]);
            }
        }
        parent::beforeSave();
    }

    protected function afterLoad(&$result)
    {
        if (isset($result['attribute_set_id'])) {
            $attributes = new AttributeCollection();
            $attributes->withSet()->columns(['code'])->where(['eav_attribute_set.id' => $result['attribute_set_id']])->where(new In('input', ['multiselect', 'checkbox']));
            foreach ($attributes as $attribute) {
                if (is_string($result[$attribute['code']])) {
                    $result[$attribute['code']] = explode(',', $result[$attribute['code']]);
                }
            }
        } elseif (isset($result[0]['attribute_set_id'])) {
            $attributes = new AttributeCollection();
            $attributes->withSet()->columns(['code'])->where(['eav_attribute_set.id' => $result[0]['attribute_set_id']])->where(new In('input', ['multiselect', 'checkbox']));
            foreach ($attributes as $attribute) {
                if (is_string($result[0][$attribute['code']])) {
                    $result[0][$attribute['code']] = explode(',', $result[0][$attribute['code']]);
                }
            }
        }
        parent::afterLoad($result);
    }
}
