<?php

namespace Redseanet\Retailer\ViewModel\Resource;

class Modal extends Explorer
{
    public function getSubmitUrl()
    {
        return $this->getBaseUrl('retailer/resource/upload/');
    }

    public function getStore()
    {
        return parent::getStore()->getId();
    }

    public function getChildrenCategories($id = 0, $title = null)
    {
        $child = parent::getChildrenCategories($id, $title);
        $child->setVariable('prefix', 'modal-upload-');
        return $child;
    }
}
