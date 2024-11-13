<?php

namespace Redseanet\Lib\Model\Collection\Eav;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Model\AbstractCollection;

class Attribute extends AbstractCollection
{
    protected $hasSortOrder = false;

    protected function construct()
    {
        $this->init('eav_attribute');
    }

    public function withLabel($languageId = null)
    {
        if (is_null($languageId)) {
            $languageId = Bootstrap::getLanguage()->getId();
        } elseif (is_array($languageId) || $languageId instanceof Language) {
            $languageId = $languageId['id'];
        }
        $this->select->join('eav_attribute_label', 'eav_attribute.id=eav_attribute_label.attribute_id', ['label'], 'left')
                ->where(['eav_attribute_label.language_id' => $languageId]);
        return $this;
    }

    public function withGroup()
    {
        if (!$this->hasSortOrder) {
            $this->select->join('eav_entity_attribute', 'eav_entity_attribute.attribute_id=eav_attribute.id', ['sort_order'], 'left');
            $this->hasSortOrder = true;
        }
        $this->select->join('eav_attribute_group', 'eav_entity_attribute.attribute_group_id=eav_attribute_group.id', ['attribute_group' => 'name', 'attribute_group_id' => 'id', 'attribute_group_is_hidden' => 'is_hidden'], 'left');
        return $this;
    }

    public function withSet()
    {
        if (!$this->hasSortOrder) {
            $this->select->join('eav_entity_attribute', 'eav_entity_attribute.attribute_id=eav_attribute.id', ['sort_order'], 'left');
            $this->hasSortOrder = true;
        }
        $this->select->join('eav_attribute_set', 'eav_entity_attribute.attribute_set_id=eav_attribute_set.id', ['attribute_set' => 'name', 'attribute_set_id' => 'id'], 'left');
        return $this;
    }
}
