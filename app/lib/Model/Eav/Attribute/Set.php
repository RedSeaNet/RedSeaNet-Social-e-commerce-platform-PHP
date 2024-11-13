<?php

namespace Redseanet\Lib\Model\Eav\Attribute;

use Redseanet\Lib\Model\AbstractModel;

class Set extends AbstractModel
{
    protected function construct()
    {
        $this->init('eav_attribute_set', 'id', ['id', 'type_id', 'name']);
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        parent::afterSave();
        $attributeSetId = $this->getId();
        $tableGateway = $this->getTableGateway('eav_entity_attribute');
        $tableGateway->delete(['attribute_set_id' => $attributeSetId]);
        if (!empty($this->storage['attributes'])) {
            foreach ($this->storage['attributes'] as $groupId => $attributes) {
                foreach ($attributes as $sortOrder => $attributeId) {
                    $tableGateway->insert([
                        'attribute_set_id' => $attributeSetId,
                        'attribute_group_id' => $groupId,
                        'attribute_id' => $attributeId,
                        'sort_order' => $sortOrder
                    ]);
                }
            }
        }
        $tableGatewayProduct = $this->getTableGateway('eav_entity_custom_attribute_product');
        $tableGatewayProduct->delete(['attribute_set_id' => $attributeSetId]);
        if (isset($this->storage['customattributes']) && is_array($this->storage['customattributes']) && count($this->storage['customattributes']) > 0) {
            foreach ($this->storage['customattributes'] as $sortOrderCustom => $customattributeId) {
                if (!empty($customattributeId)) {
                    $tableGatewayProduct->insert([
                        'attribute_set_id' => $attributeSetId,
                        'attribute_id' => $customattributeId,
                        'sort_order' => $sortOrderCustom
                    ]);
                }
            }
        }
        $this->flushList('eav_attribute');
        $this->commit();
    }
}
