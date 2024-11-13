<?php

namespace Redseanet\Admin\ViewModel\Api\Grid;

class Attribute extends RestRole
{
    protected $editUrl = '';
    protected $action = [];

    public function getRowLink($item)
    {
        if ($this->editUrl === '') {
            $this->editUrl = $this->getAdminUrl('api_rest_attribute/edit/?id=');
        }
        return $this->editUrl . $item['id'];
    }
}
