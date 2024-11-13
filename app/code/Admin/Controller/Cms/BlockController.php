<?php

namespace Redseanet\Admin\Controller\Cms;

use Redseanet\Cms\Model\Block as Model;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class BlockController extends AuthActionController
{
    public function indexAction()
    {
        $root = $this->getLayout('admin_cms_block_list');
        return $root;
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_cms_block_edit');
        if ($id = $this->getRequest()->getQuery('id')) {
            $model = new Model();
            $model->load($id);
            $root->getChild('edit', true)->setVariable('model', $model);
            $root->getChild('head')->setTitle('Edit Block / CMS');
        } else {
            $root->getChild('head')->setTitle('Add New Block / CMS');
        }
        return $root;
    }

    public function deleteAction()
    {
        return $this->doDelete('\\Redseanet\\Cms\\Model\\Block', ':ADMIN/cms_block/');
    }

    public function saveAction()
    {
        return $this->doSave(
            '\\Redseanet\\Cms\\Model\\Block',
            ':ADMIN/cms_block/',
            ['code', 'language_id'],
            function ($model, $data) {
                $userArray = (new Segment('admin'))->get('user');
                $user = new User();
                $user->load($userArray['id']);
                if ($user->getStore()) {
                    if ($model->getId() && $model->offsetGet('store_id') != $user->getStore()->getId()) {
                        throw new \Exception('Not allowed to save.');
                    }
                    $model->setData('store_id', $user->getStore()->getId());
                } elseif (empty($data['store_id'])) {
                    $model->setData('store_id', null);
                }
            }
        );
    }
}
