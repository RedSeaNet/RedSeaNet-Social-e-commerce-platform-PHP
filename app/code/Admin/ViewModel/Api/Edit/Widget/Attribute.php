<?php

namespace Redseanet\Admin\ViewModel\Api\Edit\Widget;

use Redseanet\Api\Model\Collection\Rest\Attribute as PrivilegeCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Model\Collection\Eav\Attribute as AttributeCollection;

class Attribute extends Template
{
    public function getAttributes()
    {
        $collection = new AttributeCollection();
        $collection->withLabel(Bootstrap::getLanguage()->getId())
                ->columns(['code'])
                ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', ['entity_type' => 'code'], 'left')
                ->order('eav_entity_type.code ASC, eav_attribute.id ASC');
        $collection->load(true, true);
        $config = $this->getConfig()['api'];
        $result = empty($config['attributes']) ? [] : $config['attributes'];
        foreach ($collection as $item) {
            if (!isset($result[$item['entity_type']])) {
                $result[$item['entity_type']] = [];
            }
            $result[$item['entity_type']][$item['code']] = $item['label'];
        }
        return $result;
    }

    public function getPrivileges()
    {
        $collection = new PrivilegeCollection();
        $collection->where(['api_rest_attribute.role_id' => $this->getQuery('id')]);
        $result = [];
        foreach ($collection as $item) {
            $result[$item['operation'] . $item['resource']] = explode(',', $item['attributes']);
        }
        return $result;
    }
}
