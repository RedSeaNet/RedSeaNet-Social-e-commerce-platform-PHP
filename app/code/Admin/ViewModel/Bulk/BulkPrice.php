<?php

namespace Redseanet\Admin\ViewModel\Bulk;

use Redseanet\Lib\ViewModel\Template;

class BulkPrice extends Template
{
    protected $template = 'admin/bulk/price';

    public function getPrice()
    {
        $value = $this->getVariable('item')['value'];
        return $value ? json_decode($value, true) : [];
    }
}
