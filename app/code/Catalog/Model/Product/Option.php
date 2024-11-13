<?php

namespace Redseanet\Catalog\Model\Product;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Predicate\In;

class Option extends AbstractModel
{
    protected $languageId;

    protected function construct()
    {
        $this->init('product_option', 'id', ['id', 'product_id', 'input', 'is_required', 'sku', 'price', 'is_fixed', 'sort_order', 'eav_attribute_id']);
    }

    protected function getLanguageId()
    {
        if (!$this->languageId) {
            $this->languageId = Bootstrap::getLanguage()->getId();
        }
        return $this->languageId;
    }

    public function getLabel($languageId = null)
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('product_option_title');
            $result = $tableGateway->select([
                'option_id' => $this->getId(),
                'language_id' => is_null($languageId) ? $languageId : $this->getLanguageId()
            ])->toArray();
        }
        return empty($result) ? '' : $result[0]['title'];
    }

    public function getValues()
    {
        if (!empty($this->storage['id'])) {
            if (!isset($this->storage['value'])) {
                if (in_array($this->storage['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
                    $tableGateway = $this->getTableGateway('product_option_value');
                    $select = $tableGateway->getSql()->select();
                    $select->where(['option_id' => $this->getId()]);
                    //                    $select->join('product_option_value_title', 'product_option_value.id=product_option_value_title.value_id', ['title'], 'left')
                    //                            ->where(['product_option_value_title.language_id' => $this->getLanguageId()])
                    //                            ->order('sort_order ASC');
                    $titleSubSelect = new Select();
                    $titleSubSelect->from('product_option_value_title');
                    $titleSubSelect->columns(['title']);
                    if ($this->languageId) {
                        $titleSubSelect->where('`product_option_value_title`.`language_id`=' . $this->languageId);
                    } else {
                        $titleSubSelect->where('product_option_value_title.language_id=' . Bootstrap::getLanguage()->getId());
                    }
                    $titleSubSelect->where('`product_option_value_title`.`value_id`=`product_option_value`.`id`');
                    $titleSubSelect->limit(1);

                    $defaltTitleSubSelect = new Select();
                    $defaltTitleSubSelect->from('product_option_value_title');
                    $defaltTitleSubSelect->columns(['title']);
                    $defaltTitleSubSelect->where('`product_option_value_title`.`value_id`=`product_option_value`.`id`');
                    $defaltTitleSubSelect->limit(1);
                    $select->columns(['id', 'option_id', 'sku', 'price', 'is_fixed', 'sort_order', 'title' => $titleSubSelect, 'default_title' => $defaltTitleSubSelect]);
                    //                    echo $select->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
                    //                    exit('----');
                    $this->storage['value'] = $tableGateway->selectWith($select)->toArray();
                } else {
                    $this->storage['value'] = [];
                }
            }
            return $this->storage['value'];
        }
        return [];
    }

    public function getValue($value, $titleOnly = true, $languageId = null)
    {
        if (!empty($this->storage['id'])) {
            if (in_array($this->storage['input'], ['select', 'radio', 'checkbox', 'multiselect'])) {
                $tableGateway = $this->getTableGateway('product_option_value');
                $select = $tableGateway->getSql()->select();
                $select->where(['option_id' => $this->getId(), 'product_option_value.id' => $value]);
                //                $select->join('product_option_value_title', 'product_option_value.id=product_option_value_title.value_id', ['title'], 'left')
                //                        ->where(['product_option_value_title.language_id' => $this->getLanguageId()]);
                $titleSubSelect = new Select();
                $titleSubSelect->from('product_option_value_title');
                $titleSubSelect->columns(['title']);
                if ($languageId) {
                    $titleSubSelect->where('`product_option_value_title`.`language_id`=' . intval($languageId));
                } elseif ($this->languageId) {
                    $titleSubSelect->where('`product_option_value_title`.`language_id`=' . $this->languageId);
                } else {
                    $titleSubSelect->where('`product_option_value_title`.`language_id`=' . Bootstrap::getLanguage()->getId());
                }
                $titleSubSelect->where('`product_option_value_title`.`value_id`=`product_option_value`.`id`');
                $titleSubSelect->limit(1);

                $defaltTitleSubSelect = new Select();
                $defaltTitleSubSelect->from('product_option_value_title');
                $defaltTitleSubSelect->columns(['title']);
                $defaltTitleSubSelect->where('`product_option_value_title`.`value_id`=`product_option_value`.`id`');
                $defaltTitleSubSelect->limit(1);
                $select->columns(['id', 'option_id', 'sku', 'price', 'is_fixed', 'sort_order', 'title' => $titleSubSelect, 'default_title' => $defaltTitleSubSelect]);
                //echo $select->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
                $result = $tableGateway->selectWith($select)->toArray();
                if ($result) {
                    return $titleOnly ? $result[0]['title'] : $result[0];
                }
            } else {
                return $value;
            }
        }
        return '';
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        if (!$this->getId()) {
            return false;
        } elseif (!$this->isLoaded) {
            $obj = (new static())->load($this->getId());
        } else {
            $obj = $this;
        }
        if ($this->offsetGet('product_id') == $obj->offsetGet('product_id')) {
            return true;
        } else {
            $this->setId(null);
            return false;
        }
    }

    protected function afterSave()
    {
        $languageId = Bootstrap::getLanguage()->getId();
        if ($this->storage['label']) {
            $tableGateway = $this->getTableGateway('product_option_title');
            $this->upsert(['title' => $this->storage['label']], ['option_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
        }
        $oldValueIds = [];
        if ($this->storage['value']) {
            $tableGateway = $this->getTableGateway('product_option_value');
            $titleGateway = $this->getTableGateway('product_option_value_title');

            $oldProductOptionValuetableGateway = $this->getTableGateway('product_option_value');
            $select = $tableGateway->getSql()->select();
            $select->where(['option_id' => $this->getId()]);
            $oldProductOptionValue = $oldProductOptionValuetableGateway->selectWith($select)->toArray();

            foreach ($oldProductOptionValue as $key => $value) {
                $oldValueIds[] = $value['id'];
            }
            $valueUpdateId = [];
            foreach ($this->storage['value']['sku'] as $order => $sku) {
                if ($this->storage['value']['label'][$order]) {
                    if (count($oldValueIds) > 0) {
                        if ($this->storage['value']['id'][$order] != '' && in_array($this->storage['value']['id'][$order], $oldValueIds)) {
                            $valueUpdateId[] = $this->storage['value']['id'][$order];
                            $tableGateway->update([
                                'sku' => $sku,
                                'price' => (float) $this->storage['value']['price'][$order],
                                'is_fixed' => $this->storage['value']['is_fixed'][$order],
                                'sort_order' => (int) $order,
                                'option_id' => $this->getId(),
                                'eav_attribute_option_id' => !empty($this->storage['value']['eav_attribute_option_id'][$order]) ? (int) $this->storage['value']['eav_attribute_option_id'][$order] : null
                            ], ['id' => $this->storage['value']['id'][$order]]);
                            $valueId = $this->storage['value']['id'][$order];
                        } else {
                            $tableGateway->insert([
                                'id' => null,
                                'sku' => $sku,
                                'price' => (float) $this->storage['value']['price'][$order],
                                'is_fixed' => $this->storage['value']['is_fixed'][$order],
                                'sort_order' => (int) $order,
                                'option_id' => $this->getId(),
                                'eav_attribute_option_id' => !empty($this->storage['value']['eav_attribute_option_id'][$order]) ? (int) $this->storage['value']['eav_attribute_option_id'][$order] : null
                            ]);
                            $valueId = $tableGateway->getLastInsertValue();
                        }
                    } else {
                        $tableGateway->insert([
                            'id' => null,
                            'sku' => $sku,
                            'price' => (float) $this->storage['value']['price'][$order],
                            'is_fixed' => $this->storage['value']['is_fixed'][$order],
                            'sort_order' => (int) $order,
                            'option_id' => $this->getId(),
                            'eav_attribute_option_id' => !empty($this->storage['value']['eav_attribute_option_id'][$order]) ? (int) $this->storage['value']['eav_attribute_option_id'][$order] : null
                        ]);
                        $valueId = $tableGateway->getLastInsertValue();
                    }
                    $this->upsert(['title' => $this->storage['value']['label'][$order]], ['value_id' => $valueId, 'language_id' => $languageId], $titleGateway);
                }
            }
            $shouldDelecteIds = array_diff($oldValueIds, $valueUpdateId);
            if (count($shouldDelecteIds) > 0) {
                $tableGateway->delete(['option_id' => $this->getId(), new In('id', $shouldDelecteIds)]);
            }
        }
        parent::afterSave();
    }
}
