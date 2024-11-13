<?php

namespace Redseanet\Admin\ViewModel\Customer\Edit\Attribute;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Collection\Eav\Attribute\Group as Collection;

class Group extends Template
{
    protected $deleteUrl = '';
    protected $saveUrl = '';

    public function getGroups()
    {
        $collection = new Collection();
        $collection->join('eav_entity_type', 'eav_entity_type.id=eav_attribute_group.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Customer::ENTITY_TYPE]);
        return $collection;
    }

    public function getAttributes()
    {
        $attributes = new Attribute();
        $attributes->withLabel(Bootstrap::getLanguage()->getId())
                ->join('eav_entity_attribute', 'eav_entity_attribute.attribute_id=eav_attribute.id', ['attribute_set_id', 'attribute_group_id', 'sort_order'], 'left')
                ->order('attribute_group_id, sort_order')
                ->columns(['id'])
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [], 'left')
                ->where(['eav_entity_type.code' => Customer::ENTITY_TYPE]);
        $result = [];
        $sid = $this->getVariable('parent')->getVariable('model')->getId();
        foreach ($attributes as $attribute) {
            $gid = $attribute['attribute_set_id'] == $sid ? (int) $attribute['attribute_group_id'] : 0;
            if (!isset($result[$gid])) {
                $result[$gid] = [];
            }
            $result[$gid][$attribute['id']] = $attribute;
        }
        return $result;
    }

    public function getDeleteUrl()
    {
        if (!$this->deleteUrl) {
            $this->deleteUrl = $this->getAdminUrl('customer_attribute_group/delete/');
        }
        return $this->deleteUrl;
    }

    public function getSaveUrl()
    {
        if (!$this->saveUrl) {
            $this->saveUrl = $this->getAdminUrl('customer_attribute_group/save/');
        }
        return $this->saveUrl;
    }
}
