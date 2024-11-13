<?php

namespace Redseanet\Lib\Model\Eav;

use Exception;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Language;
use Laminas\Db\Sql\Where;

class Attribute extends AbstractModel
{
    protected function construct()
    {
        $this->init('eav_attribute', 'id', ['id', 'type_id', 'code', 'type', 'input', 'validation', 'is_required', 'default_value', 'is_unique', 'searchable', 'filterable', 'comparable', 'sortable', 'multilingual']);
    }

    public function getOption($option, $language = false)
    {
        if ($this->getId() && isset($this->storage['input']) && in_array($this->storage['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
            $tableGateway = $this->getTableGateway('eav_attribute_option');
            $select = $tableGateway->getSql()->select();
            $select->join('eav_attribute_option_label', 'eav_attribute_option_label.option_id=eav_attribute_option.id', ['label'], 'left')
                    ->order('sort_order')
                    ->columns(['id', 'sort_order', 'code'])
                    ->where(['attribute_id' => $this->getId()] +
                            (is_numeric($option) || !is_scalar($option) ?
                                    ['eav_attribute_option.id' => is_scalar($option) ? $option : $option['id']] :
                                    ['eav_attribute_option_label.label' => $option]));
            if (!$language) {
                $languageId = Bootstrap::getLanguage()->getId();
            } else {
                $languageId = is_array($language) || $language instanceof Language ? $language['id'] : $language;
            }
            $select->where(['language_id' => $languageId]);
            $sql = md5($select->getSqlString($this->getContainer()->get('dbAdapter')->getPlatform()));
            if (!($result = $this->fetchList($sql, 'eav_attribute_option'))) {
                $result = $tableGateway->selectWith($select)->toArray();
                $this->addCacheList($sql, $result, 'eav_attribute_option');
            }
            if (count($result)) {
                return is_numeric($option) || !is_scalar($option) ? $result[0]['label'] : $result[0]['id'];
            }
        }
        return '';
    }

    public function getOptions($language = false, $widthCode = false)
    {
        if ($this->getId() && isset($this->storage['input']) && in_array($this->storage['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
            if (empty($this->storage['source']) || !is_subclass_of($this->storage['source'], '\\Redseanet\\Lib\\Source\\SourceInterface')) {
                $tableGateway = $this->getTableGateway('eav_attribute_option');
                $select = $tableGateway->getSql()->select();
                $select->join('eav_attribute_option_label', 'eav_attribute_option_label.option_id=eav_attribute_option.id', ['label', 'language_id'], 'left')
                        ->order('sort_order')
                        ->columns(['id', 'sort_order', 'code'])
                        ->where(['attribute_id' => $this->getId()]);
                if ($language) {
                    $languageId = is_array($language) || $language instanceof Language ? $language['id'] : $language;
                    $select->where(['language_id' => $languageId]);
                }
                $sql = md5($select->getSqlString($this->getContainer()->get('dbAdapter')->getPlatform()));
                if (!($result = $this->fetchList($sql, 'eav_attribute_option'))) {
                    $result = $tableGateway->selectWith($select)->toArray();
                    $this->addCacheList($sql, $result, 'eav_attribute_option');
                }
                $options = $language ? [] : ['order' => []];
                foreach ($result as $item) {
                    if ($language === false) {
                        if (!isset($options[$item['id']])) {
                            $options[$item['id']] = [];
                        }
                        $options[$item['id']][$item['language_id']] = $item['label'];
                        $options['order'][$item['id']] = $item['sort_order'];
                        $options['code'][$item['id']] = $item['code'];
                    } else {
                        if ($widthCode) {
                            $options[$item['id']] = ['label' => $item['label'], 'code' => $item['code']];
                        } else {
                            $options[$item['id']] = $item['label'];
                        }
                    }
                }
                return $options;
            } else {
                return (new $this->storage['source']())->getSourceArray();
            }
        }
        return $language ? [] : ['order' => []];
    }

    public function getLabel($language = false)
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('eav_attribute_label');
            $select = $tableGateway->getSql()->select();
            $select->columns(['label', 'language_id'])
                    ->where(['attribute_id' => $this->getId()]);
            if ($language) {
                $languageId = is_array($language) || $language instanceof Language ? $language['id'] : $language;
                $select->where(['language_id' => $languageId]);
            }
            $result = $tableGateway->selectWith($select)->toArray();
            if ($language === false) {
                $label = [];
                foreach ($result as $item) {
                    $label[$item['language_id']] = $item['label'];
                }
                return $label;
            }
            return count($result) ? $result[0]['label'] : '';
        }
        return '';
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        parent::afterSave();
        try {
            if (isset($this->storage['label'])) {
                $tableGateway = $this->getTableGateway('eav_attribute_label');
                $tableGateway->delete(['attribute_id' => $this->getId()]);
                foreach ($this->storage['label'] as $id => $value) {
                    $tableGateway->insert(['label' => $value, 'attribute_id' => $this->getId(), 'language_id' => $id]);
                }
            }
            if (isset($this->storage['option']) && isset($this->storage['input']) && in_array($this->storage['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
                $tableGateway = $this->getTableGateway('eav_attribute_option');
                $existed = [];
                $new = [];
                foreach ($this->storage['option'] as $languageId => $options) {
                    foreach ($options as $id => $label) {
                        if ($id < 0) {
                            if (!isset($existed[-$id])) {
                                $existed[-$id] = [$languageId => $label];
                            } else {
                                $existed[-$id][$languageId] = $label;
                            }
                        } else {
                            if (!isset($new[$id])) {
                                $new[$id] = [$languageId => $label];
                            } else {
                                $new[$id][$languageId] = $label;
                            }
                        }
                    }
                }
                $where = new Where();
                $where->equalTo('attribute_id', $this->getId());
                if ($existed) {
                    $where->notIn('id', array_keys($existed));
                }
                $tableGateway->delete($where);
                $labelGateway = $this->getTableGateway('eav_attribute_option_label');
                foreach ($existed as $id => $option) {
                    if (isset($this->storage['option-order'][-$id]) && isset($this->storage['option-code'][-$id])) {
                        $tableGateway->update(['sort_order' => (int) $this->storage['option-order'][-$id], 'code' => $this->storage['option-code'][-$id]], ['attribute_id' => $this->getId(), 'id' => $id]);
                    }
                    foreach ($option as $languageId => $label) {
                        $this->upsert([
                            'label' => $label
                        ], [
                            'option_id' => $id,
                            'language_id' => $languageId
                        ], $labelGateway);
                    }
                }
                foreach ($new as $id => $option) {
                    $tableGateway->insert(['attribute_id' => $this->getId(), 'sort_order' => (isset($this->storage['option-order'][$id]) ? (int) $this->storage['option-order'][$id] : 0), 'code' => (isset($this->storage['option-code'][$id]) ? $this->storage['option-code'][$id] : '')]);
                    $optionId = $tableGateway->getLastInsertValue();
                    foreach ($option as $languageId => $label) {
                        $this->insert([
                            'label' => $label,
                            'option_id' => $optionId,
                            'language_id' => $languageId
                        ], $labelGateway);
                    }
                }
                $this->flushList('eav_attribute_option');
            }
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
