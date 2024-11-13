<?php

namespace Redseanet\Retailer\Model;

use Redseanet\Lib\Model\AbstractModel;

class StoreTemplate extends AbstractModel
{
    protected function construct()
    {
        $this->init('store_decoration_template', 'id', ['id', 'template_name', 'code_model', 'src_model', 'stable_params', 'status', 'store_id', 'parent_id', 'page_type']);
    }
}
