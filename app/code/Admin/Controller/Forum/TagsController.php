<?php

namespace Redseanet\Admin\Controller\Forum;

use Redseanet\Forum\Model\Tags as Model;
use Redseanet\Forum\Model\Collection\Tags as Collection;
use Redseanet\Lib\Controller\AuthActionController;

class TagsController extends AuthActionController
{
    public function indexAction()
    {
        $collection = new Collection();
        $collection->withName();
        $root = $this->getLayout('admin_forum_tags_list');
        $root->getChild('grid', true)->setVariable('tags', $collection);
        return $root;
    }

    public function editAction()
    {
        $query = $this->getRequest()->getQuery();
        $root = $this->getLayout('admin_forum_tags_edit');
        $model = new Model();
        if (isset($query['id'])) {
            $model->load($query['id']);
            $root->getChild('head')->setTitle('Edit Hashtags / Forum Management');
        } else {
            $root->getChild('head')->setTitle('Add hashtags / Forum Management');
        }
        $root->getChild('edit', true)->setVariable('model', $model);
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Forum\\Model\\Tags', ':ADMIN/forum_tags/');
    }

    public function saveAction()
    {
        return $this->doSave('\\Redseanet\\Forum\\Model\\Tags', ':ADMIN/forum_tags/', ['sort_order', 'sys_recommended', 'name']);
    }
}
