<?php

namespace Redseanet\Admin\ViewModel\Catalog\Edit\Product;

use Redseanet\Lib\Source\Eav\Attribute\Input;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Bootstrap;

class Options extends Tab
{
    protected $inputOptions = [];

    public function getInputOptions()
    {
        if (empty($this->inputOptions)) {
            $this->inputOptions = (new Input())->getSourceArray();
        }
        return $this->inputOptions;
    }

    public function getCustomAttributes($attributeSetId = '')
    {
        $attributes = new Attribute();
        $attributes->withLabel(Bootstrap::getLanguage()->getId())
                ->join('eav_entity_custom_attribute_product', 'eav_entity_custom_attribute_product.attribute_id=eav_attribute.id', ['attribute_set_id', 'sort_order'], 'left')
                ->order('sort_order')
                ->columns(['id', 'type_id', 'code', 'type', 'input', 'is_required', 'default_value', 'is_unique'])
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Product::ENTITY_TYPE]);
        if (!empty($attributeSetId)) {
            $attributes->where(['eav_entity_custom_attribute_product.attribute_set_id' => $attributeSetId]);
        }
        return $attributes;
    }
}
