<?php

namespace Redseanet\Admin\ViewModel\Resource;

use Redseanet\Resource\Source\Category;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;

class Modal extends Explorer
{
    public function getCategorySource()
    {
        return (new Category())->getSourceArray();
    }

    public function getSubmitUrl()
    {
        return $this->getAdminUrl('resource_resource/upload/');
    }

    public function getStore()
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $store = $user->getStore();
        if ($store) {
            return $store->getId();
        } else {
            return (new Store())->getSourceArray();
        }
    }

    public function getChildrenCategories($id = 0, $title = null)
    {
        $child = parent::getChildrenCategories($id, $title);
        $child->setVariable('prefix', 'modal-upload-');
        return $child;
    }
}
