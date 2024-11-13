<?php

namespace Redseanet\Retailer\Model\Collection;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractCollection;

class StoreTemplateCollection extends AbstractCollection
{
    protected function construct()
    {
        $this->init('store_decoration_template', 'id', ['id', 'template_name', 'store_id', 'stable_params', 'parent_id', 'page_type']);
    }

    public function storeTemplateList($store = null, $status = '')
    {
        if (is_null($store)) {
            $store = Bootstrap::getStore()->getId();
        } elseif (is_object($store) || is_array($store)) {
            $store = $store['id'];
        }

        if (!empty($status)) {
            $this->select->where->equalTo('store_id', $store)->equalTo('status', $status)->equalTo('parent_id', 0);
        } else {
            $this->select->where->equalTo('store_id', $store)->equalTo('parent_id', 0);
        }
        return $this;
    }

    public function storeCustomizeTemplate($store, $template_id, $page_type)
    {
        $this->select->where->equalTo('store_id', $store)->equalTo('parent_id', $template_id)->equalTo('page_type', $page_type);
        return $this;
    }
}
